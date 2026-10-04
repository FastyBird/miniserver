<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use Psr\Http\Server\MiddlewareInterface;

final class RouteGroup
{

	public function __construct(
		private string $pattern,
		private RouteCollector $routeCollector,
	)
	{
	}

	/**
	 * Add middleware to the route group
	 */
	public function addMiddleware(MiddlewareInterface $middleware): void
	{
		$this->routeCollector->addMiddleware($middleware);
	}

	/**
	 * Get the RouteGroup's pattern
	 */
	public function getPattern(): string
	{
		return $this->pattern;
	}

	public function getRouteCollector(): RouteCollector
	{
		return $this->routeCollector;
	}

}
