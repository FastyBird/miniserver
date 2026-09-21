<?php declare(strict_types = 1);

/**
 * UrlFormat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           10.06.24
 */

namespace FastyBird\Module\Devices\Middleware;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions as JsonApiExceptions;
use FastyBird\Core\Http as SlimRouterHttp;
use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Router;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use function str_replace;

/**
 * Response routes fixer
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class UrlFormat implements MiddlewareInterface
{

	public function __construct(private bool $usePrefix)
	{
	}

	/**
	 * @throws JsonApiExceptions\JsonApi
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = $handler->handle($request);

		$route = $request->getAttribute(SlimRouterRouting\Router::ROUTE);

		if ($route instanceof SlimRouterRouting\Route) {
			$body = $response->getBody();
			$body->rewind();

			$content = $body->getContents();

			switch ($route->getName()) {
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNELS:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_RELATIONSHIP:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTIES:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_RELATIONSHIP:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_CHILDREN:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_PROPERTY_STATE:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROLS:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROL:
				case Devices\Constants::ROUTE_NAME_DEVICE_CHANNEL_CONTROL_RELATIONSHIP:
					$content = str_replace(
						'\/api' . ($this->usePrefix ? '\/' . Metadata\Constants::MODULE_DEVICES_PREFIX : '') . '\/v1\/channels',
						'\/api' . ($this->usePrefix ? '\/' . Metadata\Constants::MODULE_DEVICES_PREFIX : '')
							. '\/v1\/devices\/' . $route->getArgument(Router\ApiRoutes::URL_DEVICE_ID) . '\/channels',
						$content,
					);

					break;
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICES:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_RELATIONSHIP:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PARENTS:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CHILDREN:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTIES:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_RELATIONSHIP:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_CHILDREN:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_PROPERTY_STATE:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROLS:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL:
				case Devices\Constants::ROUTE_NAME_CONNECTOR_DEVICE_CONTROL_RELATIONSHIP:
					$content = str_replace(
						'\/api' . ($this->usePrefix ? '\/' . Metadata\Constants::MODULE_DEVICES_PREFIX : '') . '\/v1\/devices',
						'\/api' . ($this->usePrefix ? '\/' . Metadata\Constants::MODULE_DEVICES_PREFIX : '')
							. '\/v1\/connectors\/' . $route->getArgument(
								Router\ApiRoutes::URL_CONNECTOR_ID,
							) . '\/devices',
						$content,
					);

					break;
			}

			$response = $response->withBody(SlimRouterHttp\Stream::fromBodyString($content));
		}

		return $response;
	}

}
