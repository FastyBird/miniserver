<?php declare(strict_types = 1);

/**
 * HttpApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Schemas;
use FastyBird\Core\Values\Types\Sources;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use React\Http;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_merge;
use function base64_encode;
use function count;
use function hash;
use function http_build_query;
use function implode;
use function md5;
use function preg_match_all;
use function sprintf;
use function strval;
use function time;
use const DIRECTORY_SEPARATOR;

/**
 * Device http api interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class HttpApi
{

	use Nette\SmartObject;

	private const REQUEST_AUTHORIZATION_HEADER = 'Authorization';

	private const RESPONSE_AUTHENTICATION_RESPONSE_HEADER = 'WWW-Authenticate';

	protected const AUTHORIZATION_BASIC = 'basic';

	protected const AUTHORIZATION_DIGEST = 'digest';

	/** @var array<string, string> */
	private array $validationSchemas = [];

	public function __construct(
		protected readonly Services\HttpClientFactory $httpClientFactory,
		protected readonly Helpers\MessageBuilder $messageBuilder,
		protected readonly Shelly\Logger $logger,
		protected readonly Schemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @template T of Messages\Message
	 *
	 * @param class-string<T> $message
	 *
	 * @return T
	 *
	 * @throws ShellyExceptions\HttpApiError
	 */
	protected function createMessage(string $message, Utils\ArrayHash $data): Messages\Message
	{
		try {
			return $this->messageBuilder->create(
				$message,
				(array) Utils\Json::decode(Utils\Json::encode($data), forceArrays: true),
			);
		} catch (ShellyExceptions\Runtime $ex) {
			throw new ShellyExceptions\HttpApiError('Could not map data to message', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new ShellyExceptions\HttpApiError(
				'Could not create message from response',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Message\ResponseInterface> : Message\ResponseInterface)
	 *
	 * @throws ShellyExceptions\HttpApiCall
	 */
	protected function callRequest(
		Request $request,
		string|null $authorization = null,
		string|null $username = null,
		string|null $password = null,
		bool $async = true,
		bool $handlingAuthentication = false,
	): Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(
			sprintf(
				'Request: method = %s url = %s',
				$request->getMethod(),
				strval($request->getUri()),
			),
			[
				'source' => Sources\Connector::SHELLY->value,
				'type' => 'http-api',
				'request' => [
					'method' => $request->getMethod(),
					'path' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
					'body' => $request->getContent(),
				],
			],
		);

		if ($async) {
			if ($authorization === self::AUTHORIZATION_BASIC) {
				$request->withAddedHeader(
					self::REQUEST_AUTHORIZATION_HEADER,
					'Basic ' . base64_encode($username . ':' . $password),
				);
			}

			try {
				$this->httpClientFactory
					->create()
					->send($request)
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $request): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								$deferred->reject(
									new ShellyExceptions\HttpApiCall(
										'Could not get content from response body',
										$request,
										$response,
										$ex->getCode(),
										$ex,
									),
								);

								return;
							}

							$this->logger->debug(
								'Received response',
								[
									'source' => Sources\Connector::SHELLY->value,
									'type' => 'http-api',
									'request' => [
										'method' => $request->getMethod(),
										'url' => strval($request->getUri()),
										'headers' => $request->getHeaders(),
										'body' => $request->getContent(),
									],
									'response' => [
										'code' => $response->getStatusCode(),
										'body' => $responseBody,
									],
								],
							);

							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $request, $authorization, $username, $password, $handlingAuthentication): void {
							if (
								$authorization === self::AUTHORIZATION_DIGEST
								&& !$handlingAuthentication
								&& $ex instanceof Http\Message\ResponseException
								&& $ex->getResponse()->getStatusCode() === StatusCodeInterface::STATUS_UNAUTHORIZED
								&& $ex->getResponse()->hasHeader(self::RESPONSE_AUTHENTICATION_RESPONSE_HEADER)
								&& $ex->getResponse()->getHeader(self::RESPONSE_AUTHENTICATION_RESPONSE_HEADER) !== []
								&& $username !== null
								&& $password !== null
							) {
								$authHeader = $this->parseAuthentication(
									$request->getMethod(),
									$request->getUri()->getPath(),
									$username,
									$password,
									$ex->getResponse()->getHeader(self::RESPONSE_AUTHENTICATION_RESPONSE_HEADER)[0],
								);

								if ($authHeader !== null) {
									try {
										$request = $this->createRequest(
											$request->getMethod(),
											strval($request->getUri()),
											[],
											array_merge(
												$request->getHeaders(),
												[
													self::REQUEST_AUTHORIZATION_HEADER => $authHeader,
												],
											),
											$request->getBody()->getContents(),
										);

										$this->callRequest(
											$request,
											$authorization,
											null,
											null,
											true,
											true,
										)
											->then(
												static function (Message\ResponseInterface $response) use ($deferred): void {
													$deferred->resolve($response);
												},
											)
											->catch(
												static function (Throwable $ex) use ($deferred, $request): void {
													$deferred->reject(
														new ShellyExceptions\HttpApiCall(
															'Calling api endpoint failed',
															$request,
															null,
															$ex->getCode(),
															$ex,
														),
													);
												},
											);

										return;
									} catch (Throwable $ex) {
										$deferred->reject($ex);
									}
								}
							}

							$deferred->reject(
								new ShellyExceptions\HttpApiCall(
									'Calling api endpoint failed',
									$request,
									null,
									$ex->getCode(),
									$ex,
								),
							);
						},
					);
			} catch (Throwable $ex) {
				$deferred->reject($ex);
			}

			return $deferred->promise();
		}

		$options = [];

		if ($authorization === self::AUTHORIZATION_BASIC) {
			$options[GuzzleHttp\RequestOptions::AUTH] = [$username, $password];
		} elseif ($authorization === self::AUTHORIZATION_DIGEST) {
			$options[GuzzleHttp\RequestOptions::AUTH] = [$username, $password, 'digest'];
		}

		try {
			$response = $this->httpClientFactory
				->create(false)
				->send($request, $options);

			try {
				$responseBody = $response->getBody()->getContents();

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				throw new ShellyExceptions\HttpApiCall(
					'Could not get content from response body',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug(
				'Received response',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'http-api',
					'request' => [
						'method' => $request->getMethod(),
						'url' => strval($request->getUri()),
						'headers' => $request->getHeaders(),
						'body' => $request->getContent(),
					],
					'response' => [
						'code' => $response->getStatusCode(),
						'body' => $responseBody,
					],
				],
			);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			throw new ShellyExceptions\HttpApiCall(
				'Calling api endpoint failed',
				$request,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @param array<string, mixed> $params
	 * @param array<string, int|string|array<string>> $headers
	 *
	 * @throws ShellyExceptions\HttpApiError
	 */
	protected function createRequest(
		string $method,
		string $url,
		array $params = [],
		array $headers = [],
		string|null $body = null,
	): Request
	{
		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			return new Request($method, $url, $headers, $body);
		} catch (ShellyExceptions\InvalidArgument | ShellyExceptions\Runtime $ex) {
			throw new ShellyExceptions\HttpApiError('Could not create request instance', $ex->getCode(), $ex);
		}
	}

	protected function parseAuthentication(
		string $method,
		string $path,
		string $username,
		string $password,
		string $header,
	): string|null
	{
		// Parses the 'www-authenticate' header for nonce, realm and other values
		preg_match_all('#((\w+)="?([^\s"]+))#', $header, $matches);

		$serverBits = [];

		foreach ($matches[2] as $i => $key) {
			$serverBits[$key] = $matches[3][$i];
		}

		if (
			!array_key_exists('realm', $serverBits)
			|| !array_key_exists('nonce', $serverBits)
			|| !array_key_exists('qop', $serverBits)
		) {
			return null;
		}

		$algo = array_key_exists('algorithm', $serverBits) ? match (Utils\Strings::lower($serverBits['algorithm'])) {
				'sha-256', 'sha256' => 'sha256',
				default => 'md5',
		} : 'md5';

		$nc = 1;

		$clientNonce = time();

		$ha1 = hash($algo, $username . ':' . $serverBits['realm'] . ':' . $password);
		$ha2 = hash($algo, $method . ':' . $path);

		// The order of this array matters, because it affects resulting hashed val
		$response = hash(
			$algo,
			implode(
				':',
				[
					$ha1,
					$serverBits['nonce'],
					$nc,
					$clientNonce,
					'auth',
					$ha2,
				],
			),
		);

		$digestHeaderValues = [
			'username' => $username,
			'realm' => $serverBits['realm'],
			'nonce' => $serverBits['nonce'],
			'cnonce' => $clientNonce,
			'uri' => $path,
			'response' => $response,
			'qop' => $serverBits['qop'],
			'nc' => $nc,
			'algorithm' => array_key_exists('algorithm', $serverBits) ? $serverBits['algorithm'] : 'MD5',
		];

		$digestHeader = [];

		foreach ($digestHeaderValues as $key => $value) {
			$digestHeader[] = $key . '=' . $value;
		}

		return 'Digest ' . implode(', ', $digestHeader);
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws ShellyExceptions\HttpApiCall
	 * @throws ShellyExceptions\HttpApiError
	 */
	protected function validateResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		$body = $this->getResponseBody($request, $response);

		try {
			return $this->schemaValidator->validate(
				$body,
				$this->getSchema($schemaFilename),
			);
		} catch (ApplicationExceptions\Logic | DocumentsExceptions\MalformedInput | ValuesExceptions\InvalidData $ex) {
			if ($throw) {
				throw new ShellyExceptions\HttpApiCall(
					'Could not validate received response payload',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws ShellyExceptions\HttpApiCall
	 */
	private function getResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): string
	{
		try {
			$response->getBody()->rewind();

			return $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new ShellyExceptions\HttpApiCall(
				'Could not get content from response body',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws ShellyExceptions\HttpApiError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'response' . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new ShellyExceptions\HttpApiError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

}
