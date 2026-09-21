<?php declare(strict_types = 1);

/**
 * RouteGroup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Routing
 * @since          0.1.0
 *
 * @date           14.03.20
 */

namespace FastyBird\Core\Routing;

use Psr\Http\Server\MiddlewareInterface;

class RouteGroup implements IRouteGroup
{

	public function __construct(
		private string $pattern,
		private IRouteCollector $routeCollector,
	)
	{
	}

	public function addMiddleware(MiddlewareInterface $middleware): void
	{
		$this->routeCollector->addMiddleware($middleware);
	}

	public function getPattern(): string
	{
		return $this->pattern;
	}

	public function getRouteCollector(): IRouteCollector
	{
		return $this->routeCollector;
	}

}
