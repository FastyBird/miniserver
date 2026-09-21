<?php declare(strict_types = 1);

/**
 * LanApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\API;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Schemas\Tools as ToolsSchemas;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function assert;
use function count;
use function http_build_query;
use function is_scalar;
use function md5;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * NS Panel LAN API interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LanApi
{

	use Nette\SmartObject;

	public const GATEWAY_PORT = 8_081;

	private const GET_GATEWAY_INFO_MESSAGE_SCHEMA_FILENAME = 'get_gateway_info.json';

	private const GET_GATEWAY_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME = 'get_gateway_access_token.json';

	private const SYNCHRONISE_DEVICES_MESSAGE_SCHEMA_FILENAME = 'synchronise_devices.json';

	private const REPORT_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'report_device_online.json';

	private const REPORT_DEVICE_ONLINE_MESSAGE_SCHEMA_FILENAME = 'report_device_online.json';

	private const GET_SUB_DEVICES_MESSAGE_SCHEMA_FILENAME = 'get_sub_devices.json';

	private const SET_SUB_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'set_sub_device_state.json';

	private const EVENT_ERROR_MESSAGE_SCHEMA_FILENAME = 'event_error.json';

	/** @var array<string, string> */
	private array $validationSchemas = [];

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly Services\HttpClientFactory $httpClientFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly NsPanel\Logger $logger,
		private readonly ToolsSchemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\GetGatewayInfo> : Messages\Response\GetGatewayInfo)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function getGatewayInfo(
		string $ipAddress,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\GetGatewayInfo
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf('http://%s:%d/open-api/v1/rest/bridge', $ipAddress, $port),
			[
				'Content-Type' => 'application/json',
			],
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetGatewayInfo($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetGatewayInfo($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\GetGatewayAccessToken> : Messages\Response\GetGatewayAccessToken)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function getGatewayAccessToken(
		string $name,
		string $ipAddress,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\GetGatewayAccessToken
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf('http://%s:%d/open-api/v1/rest/bridge/access_token', $ipAddress, $port),
			[
				'Content-Type' => 'application/json',
			],
			[
				'app_name' => $name,
			],
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetGatewayAccessToken($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetGatewayAccessToken($request, $result);
	}

	/**
	 * @param array<mixed> $devices
	 *
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\SyncDevices> : Messages\Response\SyncDevices)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function synchroniseDevices(
		array $devices,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\SyncDevices
	{
		$deferred = new Promise\Deferred();

		$message = $this->createMessage(
			Messages\Request\SyncDevices::class,
			Utils\ArrayHash::from([
				'event' => [
					'header' => [
						'name' => NsPanel\Types\Header::DISCOVERY_REQUEST->value,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => NsPanel\Constants::NS_PANEL_API_VERSION_V1,
					],
					'payload' => [
						'endpoints' => $devices,
					],
				],
			]),
		);

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_POST,
				sprintf('http://%s:%d/open-api/v1/rest/thirdparty/event', $ipAddress, $port),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($message->toJson()),
			);

			$result = $this->callRequest($request, $async);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(
					new Exceptions\LanApiCall(
						'Could not prepare request',
						null,
						null,
						$ex->getCode(),
						$ex,
					),
				);
			}

			throw new Exceptions\LanApiError(
				'Could not prepare request',
				$ex->getCode(),
				$ex,
			);
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseSynchroniseDevices($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseSynchroniseDevices($request, $result);
	}

	/**
	 * @param array<mixed> $state
	 *
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\ReportDeviceState> : Messages\Response\ReportDeviceState)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function reportDeviceState(
		string $serialNumber,
		array $state,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\ReportDeviceState
	{
		$deferred = new Promise\Deferred();

		$message = $this->createMessage(
			Messages\Request\ReportDeviceState::class,
			Utils\ArrayHash::from([
				'event' => [
					'header' => [
						'name' => NsPanel\Types\Header::DEVICE_STATES_CHANGE_REPORT->value,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => NsPanel\Constants::NS_PANEL_API_VERSION_V1,
					],
					'endpoint' => [
						'serial_number' => $serialNumber,
					],
					'payload' => [
						'state' => $state,
					],
				],
			]),
		);

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_POST,
				sprintf('http://%s:%d/open-api/v1/rest/thirdparty/event', $ipAddress, $port),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($message->toJson()),
			);

			$result = $this->callRequest($request, $async);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(
					new Exceptions\LanApiCall(
						'Could not prepare request',
						null,
						null,
						$ex->getCode(),
						$ex,
					),
				);
			}

			throw new Exceptions\LanApiError(
				'Could not prepare request',
				$ex->getCode(),
				$ex,
			);
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseReportDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseReportDeviceState($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\ReportDeviceOnline> : Messages\Response\ReportDeviceOnline)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function reportDeviceOnline(
		string $serialNumber,
		bool $online,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\ReportDeviceOnline
	{
		$deferred = new Promise\Deferred();

		$message = $this->createMessage(
			Messages\Request\ReportDeviceOnline::class,
			Utils\ArrayHash::from([
				'event' => [
					'header' => [
						'name' => NsPanel\Types\Header::DEVICE_ONLINE_CHANGE_REPORT->value,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => NsPanel\Constants::NS_PANEL_API_VERSION_V1,
					],
					'endpoint' => [
						'serial_number' => $serialNumber,
					],
					'payload' => [
						'online' => $online,
					],
				],
			]),
		);

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_POST,
				sprintf('http://%s:%d/open-api/v1/rest/thirdparty/event', $ipAddress, $port),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($message->toJson()),
			);

			$result = $this->callRequest($request, $async);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(
					new Exceptions\LanApiCall(
						'Could not prepare request',
						null,
						null,
						$ex->getCode(),
						$ex,
					),
				);
			}

			throw new Exceptions\LanApiError(
				'Could not prepare request',
				$ex->getCode(),
				$ex,
			);
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseReportDeviceOnline($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseReportDeviceOnline($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<true> : true)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function removeDevice(
		string $serialNumber,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			$this->createRequest(
				RequestMethodInterface::METHOD_DELETE,
				sprintf('http://%s:%d/open-api/v1/rest/devices/%s', $ipAddress, $port, $serialNumber),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
			),
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($deferred): void {
					try {
						$deferred->resolve(true);
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\GetSubDevices> : Messages\Response\GetSubDevices)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function getSubDevices(
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\GetSubDevices
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf('http://%s:%d/open-api/v1/rest/devices', $ipAddress, $port),
			[
				'Content-Type' => 'application/json',
				'Authorization' => sprintf('Bearer %s', $accessToken),
			],
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetSubDevices($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetSubDevices($request, $result);
	}

	/**
	 * @param array<mixed> $state
	 *
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\SetSubDeviceState> : Messages\Response\SetSubDeviceState)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	public function setSubDeviceState(
		string $serialNumber,
		array $state,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\SetSubDeviceState
	{
		$deferred = new Promise\Deferred();

		$message = $this->createMessage(
			Messages\Request\SetSubDeviceState::class,
			Utils\ArrayHash::from([
				'state' => $state,
			]),
		);

		try {
			$request = $this->createRequest(
				RequestMethodInterface::METHOD_PUT,
				sprintf('http://%s:%d/open-api/v1/rest/devices/%s', $ipAddress, $port, $serialNumber),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($message->toJson()),
			);

			$result = $this->callRequest($request, $async);
		} catch (Utils\JsonException $ex) {
			if ($async) {
				return Promise\reject(
					new Exceptions\LanApiCall(
						'Could not prepare request',
						null,
						null,
						$ex->getCode(),
						$ex,
					),
				);
			}

			throw new Exceptions\LanApiError(
				'Could not prepare request',
				$ex->getCode(),
				$ex,
			);
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseSetSubDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseSetSubDeviceState($request, $result);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseGetGatewayInfo(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\GetGatewayInfo
	{
		$body = $this->validateResponseBody($request, $response, self::GET_GATEWAY_INFO_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$message = $body->offsetGet('message');

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway info failed: %s', is_scalar($message) ? strval($message) : 'unknown'),
				$request,
				$response,
			);
		}

		return $this->createMessage(Messages\Response\GetGatewayInfo::class, $body);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseGetGatewayAccessToken(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\GetGatewayAccessToken
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_GATEWAY_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME,
		);

		$error = $body->offsetGet('error');

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$message = $body->offsetGet('message');

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway access token failed: %s', is_scalar($message) ? strval($message) : 'unknown'),
				$request,
				$response,
			);
		}

		return $this->createMessage(Messages\Response\GetGatewayAccessToken::class, $body);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseSynchroniseDevices(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\SyncDevices
	{
		$errorBody = $this->validateResponseBody($request, $response, self::EVENT_ERROR_MESSAGE_SCHEMA_FILENAME, false);

		if ($errorBody !== false) {
			$error = $this->createMessage(Messages\Response\ErrorEvent::class, $errorBody);

			throw new Exceptions\LanApiCall(
				sprintf('Synchronise third-party devices failed: %s', $error->getPayload()->getDescription()),
				$request,
				$response,
			);
		}

		return $this->createMessage(
			Messages\Response\SyncDevices::class,
			$this->validateResponseBody($request, $response, self::SYNCHRONISE_DEVICES_MESSAGE_SCHEMA_FILENAME),
		);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseReportDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\ReportDeviceState
	{
		$errorBody = $this->validateResponseBody($request, $response, self::EVENT_ERROR_MESSAGE_SCHEMA_FILENAME, false);

		if ($errorBody !== false) {
			$error = $this->createMessage(Messages\Response\ErrorEvent::class, $errorBody);

			throw new Exceptions\LanApiCall(
				sprintf('Report third-party device state failed: %s', $error->getPayload()->getDescription()),
				$request,
				$response,
			);
		}

		return $this->createMessage(
			Messages\Response\ReportDeviceState::class,
			$this->validateResponseBody($request, $response, self::REPORT_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME),
		);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseReportDeviceOnline(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\ReportDeviceOnline
	{
		$errorBody = $this->validateResponseBody($request, $response, self::EVENT_ERROR_MESSAGE_SCHEMA_FILENAME, false);

		if ($errorBody !== false) {
			$error = $this->createMessage(Messages\Response\ErrorEvent::class, $errorBody);

			throw new Exceptions\LanApiCall(
				sprintf('Report third-party device state failed: %s', $error->getPayload()->getDescription()),
				$request,
				$response,
			);
		}

		return $this->createMessage(
			Messages\Response\ReportDeviceOnline::class,
			$this->validateResponseBody($request, $response, self::REPORT_DEVICE_ONLINE_MESSAGE_SCHEMA_FILENAME),
		);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseGetSubDevices(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\GetSubDevices
	{
		$body = $this->validateResponseBody($request, $response, self::GET_SUB_DEVICES_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$message = $body->offsetGet('message');

			throw new Exceptions\LanApiCall(
				sprintf('Get sub-devices list failed: %s', is_scalar($message) ? strval($message) : 'unknown'),
				$request,
				$response,
			);
		}

		return $this->createMessage(Messages\Response\GetSubDevices::class, $body);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function parseSetSubDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Messages\Response\SetSubDeviceState
	{
		$body = $this->validateResponseBody($request, $response, self::SET_SUB_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			$message = $body->offsetGet('message');

			throw new Exceptions\LanApiCall(
				sprintf('Set sub-device state failed: %s', is_scalar($message) ? strval($message) : 'unknown'),
				$request,
				$response,
			);
		}

		return $this->createMessage(Messages\Response\SetSubDeviceState::class, $body);
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\LanApiCall
	 * @throws Exceptions\LanApiError
	 */
	private function validateResponseBody(
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
		} catch (ApplicationExceptions\Logic | ApplicationExceptions\MalformedInput | ToolsExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\LanApiCall(
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
	 * @throws Exceptions\LanApiCall
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
			throw new Exceptions\LanApiCall(
				'Could not get content from response body',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @template T of Messages\Message
	 *
	 * @param class-string<T> $message
	 *
	 * @return T
	 *
	 * @throws Exceptions\LanApiError
	 */
	private function createMessage(string $message, Utils\ArrayHash $data): Messages\Message
	{
		try {
			return $this->messageBuilder->create(
				$message,
				(array) Utils\Json::decode(Utils\Json::encode($data), forceArrays: true),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\LanApiError('Could not map data to message', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\LanApiError(
				'Could not create message from response',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Message\ResponseInterface> : Message\ResponseInterface)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function callRequest(
		Request $request,
		bool $async = true,
	): Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(
			sprintf(
				'Request: method = %s url = %s',
				$request->getMethod(),
				$request->getUri(),
			),
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'lan-api',
				'request' => [
					'method' => $request->getMethod(),
					'url' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
					'body' => $request->getContent(),
				],
				'connector' => [
					'id' => $this->id->toString(),
				],
			],
		);

		if ($async) {
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
									new Exceptions\LanApiCall(
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
									'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
									'type' => 'lan-api',
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
									'connector' => [
										'id' => $this->id->toString(),
									],
								],
							);

							$deferred->resolve($response);
						},
						static function (Throwable $ex) use ($deferred, $request): void {
							$deferred->reject(
								new Exceptions\LanApiCall(
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
				return Promise\reject($ex);
			}

			return $deferred->promise();
		}

		try {
			$response = $this->httpClientFactory
				->create(false)
				->send($request);

			try {
				$responseBody = $response->getBody()->getContents();

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				throw new Exceptions\LanApiCall(
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
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'lan-api',
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
					'connector' => [
						'id' => $this->id->toString(),
					],
				],
			);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			throw new Exceptions\LanApiCall(
				'Calling api endpoint failed',
				$request,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\LanApiError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'response' . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\LanApiError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

	/**
	 * @param array<string, string|array<string>>|null $headers
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\LanApiError
	 */
	private function createRequest(
		string $method,
		string $url,
		array|null $headers = null,
		array $params = [],
		string|null $body = null,
	): Request
	{
		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			return new Request($method, $url, $headers, $body);
		} catch (Exceptions\InvalidArgument $ex) {
			throw new Exceptions\LanApiError('Could not create request instance', $ex->getCode(), $ex);
		}
	}

}
