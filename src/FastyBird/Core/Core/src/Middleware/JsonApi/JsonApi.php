<?php declare(strict_types = 1);

// phpcs:ignoreFile

/**
 * JsonApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           17.04.19
 */

namespace FastyBird\Core\Middleware\JsonApi;

use FastyBird\Core\Encoding\JsonApi as Tools;
use FastyBird\Core\Exceptions as Exceptions;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Neomerx;
use Neomerx\JsonApi\Contracts;
use Neomerx\JsonApi\Schema;
use Nette\DI;
use Psr\Http\Message;
use Psr\Http\Server;
use Psr\Log;
use RuntimeException;
use Throwable;
use function class_alias;
use function class_exists;
use const JSON_PRETTY_PRINT;

/**
 * {JSON:API} formatting output handling middleware
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class JsonApi implements Server\MiddlewareInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Message\ResponseFactoryInterface $responseFactory,
		private readonly DI\Container $container,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function process(
		Message\ServerRequestInterface $request,
		Server\RequestHandlerInterface $handler,
	): Message\ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (Throwable $ex) {
			$response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_BAD_REQUEST);

			if ($ex instanceof Exceptions\JsonApi) {
				$response = $response->withStatus($ex->getCode());

				if ($ex instanceof Exceptions\JsonApiError) {
					$content = $this->getEncoder()
						->encodeError($ex->getError());

					$response->getBody()
						->write($content);
				} elseif ($ex instanceof Exceptions\JsonApiMultipleError) {
					$content = $this->getEncoder()
						->encodeErrors($ex->getErrors());

					$response->getBody()
						->write($content);
				}

			} elseif (
				class_exists('\FastyBird\Core\Exceptions\Http')
				&& $ex instanceof \FastyBird\Core\Exceptions\Http
			) {
				$response = $response->withStatus($ex->getCode());

				$content = $this->getEncoder()
					->encodeError(new Schema\Error(
						null,
						null,
						null,
						(string) $ex->getCode(),
						(string) $ex->getCode(),
						$ex->getTitle(),
						$ex->getDescription(),
					));

				$response->getBody()
					->write($content);
			} else {
				$this->logger->error('An unknown error occurred during request handling', [
					'source' => 'middleware',
					'type' => 'json:api',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				]);

				$response = $response->withStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

				$content = $this->getEncoder()
					->encodeError(new Schema\Error(
						null,
						null,
						null,
						(string) StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
						(string) $ex->getCode(),
						'Server error',
						'There was an server error, please try again later',
					));

				$response->getBody()
					->write($content);
			}
		}

		// Setup content type
		return $response
			// Content headers
			->withHeader('Content-Type', Contracts\Http\Headers\MediaTypeInterface::JSON_API_MEDIA_TYPE);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	private function getEncoder(): Tools\Encoder
	{
		$encoder = new Tools\Encoder(
			new Neomerx\JsonApi\Factories\Factory(),
			$this->container->getByType(Contracts\Schema\SchemaContainerInterface::class),
		);

		$encoder->withEncodeOptions(JSON_PRETTY_PRINT);

		$encoder->withJsonApiVersion(Contracts\Encoder\EncoderInterface::JSON_API_VERSION);

		return $encoder;
	}

}
