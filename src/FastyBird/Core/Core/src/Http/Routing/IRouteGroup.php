<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use Psr\Http\Server\MiddlewareInterface;

interface IRouteGroup
{

	/**
	 * Add middleware to the route group
	 */
	public function addMiddleware(MiddlewareInterface $middleware): void;

	/**
	 * Get the RouteGroup's pattern
	 */
	public function getPattern(): string;

	public function getRouteCollector(): IRouteCollector;

}
