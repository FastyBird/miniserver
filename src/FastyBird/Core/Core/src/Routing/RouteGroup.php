<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing;

use Override;
use Psr\Http\Server\MiddlewareInterface;

final class RouteGroup implements IRouteGroup
{

	public function __construct(
		private string $pattern,
		private IRouteCollector $routeCollector,
	)
	{
	}

	#[Override]
	public function addMiddleware(MiddlewareInterface $middleware): void
	{
		$this->routeCollector->addMiddleware($middleware);
	}

	#[Override]
	public function getPattern(): string
	{
		return $this->pattern;
	}

	#[Override]
	public function getRouteCollector(): IRouteCollector
	{
		return $this->routeCollector;
	}

}
