<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 * @since          1.0.0
 *
 * @date           08.05.21
 */

namespace FastyBird\Plugin\WebServer\Middleware;

use FastyBird\Library\SlimRouter\Routing;
use FastyBird\Plugin\WebServer\Events;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application router middleware
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Router
{

	public function __construct(
		private Routing\IRouter $router,
		private EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher?->dispatch(new Events\Request($request));

		$response = $this->router->handle($request);

		$this->dispatcher?->dispatch(new Events\Response($request, $response));

		return $response;
	}

}
