<?php declare(strict_types = 1);

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
