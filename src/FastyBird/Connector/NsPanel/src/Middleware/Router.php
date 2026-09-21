<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\NsPanel\Middleware;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Events;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as SlimRouterExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Http as SlimRouterHttp;
use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette\Utils;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function is_array;
use function strval;

/**
 * Connector HTTP server router middleware
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Router
{

	private SlimRouterHttp\ResponseFactory $responseFactory;

	public function __construct(
		private readonly NsPanel\Logger $logger,
		private readonly SlimRouterRouting\IRouter $router,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->responseFactory = new SlimRouterHttp\ResponseFactory();
	}

	private function getMessageId(ServerRequestInterface $request): string
	{
		try {
			$request->getBody()->rewind();

			$requestBody = $request->getBody()->getContents();

			$requestBody = Utils\Json::decode($requestBody, forceArrays: true);

			if (
				is_array($requestBody)
				&& array_key_exists('directive', $requestBody)
				&& is_array($requestBody['directive'])
				&& array_key_exists('header', $requestBody['directive'])
				&& is_array($requestBody['directive']['header'])
				&& array_key_exists('message_id', $requestBody['directive']['header'])
			) {
				return strval($requestBody['directive']['header']['message_id']);
			}
		} catch (Utils\JsonException | RuntimeException) {
			// Could be ignored, something is wrong with request
		}

		return Uuid\Uuid::uuid4()->toString();
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws Utils\JsonException
	 */
	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher?->dispatch(new Events\Request($request));

		try {
			$response = $this->router->handle($request);
			$response = $response->withHeader('Server', 'FastyBird NS Panel Connector');
		} catch (Exceptions\ServerRequestError $ex) {
			$this->logger->warning(
				'Request ended with error',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'router-middleware',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $request->getMethod(),
						'path' => $request->getUri()->getPath(),
					],
				],
			);

			$response = $this->responseFactory->createResponse($ex->getCode());

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString(Utils\Json::encode([
				'event' => [
					'header' => [
						'name' => Types\Header::ERROR_RESPONSE->value,
						'message_id' => $this->getMessageId($request),
						'version' => '1',
					],
					'payload' => [
						'type' => Types\ServerStatus::INTERNAL_ERROR->value,
					],
				],
			])));
		} catch (SlimRouterExceptions\Http $ex) {
			$this->logger->warning(
				'Received invalid HTTP request',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'router-middleware',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $request->getMethod(),
						'path' => $request->getUri()->getPath(),
					],
				],
			);

			$response = $this->responseFactory->createResponse($ex->getCode());

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString(Utils\Json::encode([
				'event' => [
					'header' => [
						'name' => Types\Header::ERROR_RESPONSE->value,
						'message_id' => $this->getMessageId($request),
						'version' => '1',
					],
					'payload' => [
						'type' => Types\ServerStatus::INTERNAL_ERROR->value,
					],
				],
			])));
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred during handling server HTTP request',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'router-middleware',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);

			$response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

			$response = $response->withHeader('Content-Type', Servers\Http::JSON_CONTENT_TYPE);
			$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString(Utils\Json::encode([
				'event' => [
					'header' => [
						'name' => Types\Header::ERROR_RESPONSE->value,
						'message_id' => $this->getMessageId($request),
						'version' => '1',
					],
					'payload' => [
						'type' => Types\ServerStatus::INTERNAL_ERROR->value,
					],
				],
			])));
		}

		$this->dispatcher?->dispatch(new Events\Response($request, $response));

		return $response;
	}

}
