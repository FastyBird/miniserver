<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\HomeKit\Middleware;

use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Events;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Servers;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Exceptions as HttpExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * Connector HTTP server router middleware
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Router
{

	private Http\ResponseFactory $responseFactory;

	public function __construct(
		private readonly HomeKit\Logger $logger,
		private readonly Routing\IRouter $router,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->responseFactory = new Http\ResponseFactory();
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher?->dispatch(new Events\Request($request));

		try {
			$response = $this->router->handle($request);
			$response = $response->withHeader('Server', 'FastyBird HomeKit Connector');

		} catch (HomeKitExceptions\HapRequestError $ex) {
			$this->logger->warning(
				'Request ended with error',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'router-middleware',
					'exception' => Logging\Logger::buildException($ex),
					'request' => [
						'method' => $request->getMethod(),
						'path' => $request->getUri()->getPath(),
					],
				],
			);

			$response = $this->responseFactory->createResponse($ex->getCode());

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(Http\Stream::fromBodyString(Utils\Json::encode([
				Types\Representation::STATUS->value => $ex->getError()->value,
			])));
		} catch (HttpExceptions\Http $ex) {
			$this->logger->warning(
				'Received invalid HTTP request',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'router-middleware',
					'exception' => Logging\Logger::buildException($ex),
					'request' => [
						'method' => $request->getMethod(),
						'path' => $request->getUri()->getPath(),
					],
				],
			);

			$response = $this->responseFactory->createResponse($ex->getCode());

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(Http\Stream::fromBodyString(Utils\Json::encode([
				Types\Representation::STATUS->value => Types\ServerStatus::SERVICE_COMMUNICATION_FAILURE->value,
			])));
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred during handling server HTTP request',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'router-middleware',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(Http\Stream::fromBodyString(Utils\Json::encode([
				Types\Representation::STATUS->value => Types\ServerStatus::SERVICE_COMMUNICATION_FAILURE->value,
			])));
		}

		$this->dispatcher?->dispatch(new Events\Response($request, $response));

		return $response;
	}

}
