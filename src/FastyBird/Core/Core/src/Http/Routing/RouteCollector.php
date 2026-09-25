<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Controllers;
use FastyBird\Core\Http\Middleware;
use Fig\Http\Message\RequestMethodInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use function array_merge;

/**
 * RouteCollector is used to collect routes and route groups
 * as well as generate paths and URLs relative to its environment
 */
final class RouteCollector implements IRouteCollector
{

	private Handlers\Handler $defaultInvocationHandler;

	/** @var array<IRoute> */
	private array $routes = [];

	/** @var array<IRouteGroup> */
	private array $groups = [];

	/** @var array<MiddlewareInterface> */
	private array $middleware = [];

	public function __construct(
		private ResponseFactoryInterface $responseFactory,
		private Controllers\IControllerResolver $controllerResolver,
		private IRouteParser $routeParser,
		private IRouteCollector|null $routeCollector = null,
		Handlers\Handler|null $defaultInvocationHandler = null,
		private string $pattern = '',
	)
	{
		$this->defaultInvocationHandler = $defaultInvocationHandler ?? new Handlers\RequestResponseHandler();
	}

	#[Override]
	public function setDefaultInvocationHandler(Handlers\Handler $strategy): void
	{
		$this->defaultInvocationHandler = $strategy;
	}

	#[Override]
	public function getPattern(): string
	{
		return ($this->routeCollector?->getPattern() ?? '') . $this->pattern;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getRoutes(): array
	{
		$routes = [];

		$routes = array_merge($routes, $this->routes);

		foreach ($this->groups as $group) {
			$routes = array_merge($routes, $group->getRouteCollector()->getRoutes());
		}

		return $routes;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getNamedRoute(string $name, bool $throw = true): IRoute|null
	{
		foreach ($this->routes as $route) {
			if ($name === $route->getName()) {
				return $route;
			}
		}

		foreach ($this->groups as $group) {
			$route = $group->getRouteCollector()->getNamedRoute($name, false);

			if ($route !== null) {
				return $route;
			}
		}

		if ($throw) {
			throw new Exceptions\Runtime('Named route does not exist for name: ' . $name);
		}

		return null;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function removeNamedRoute(string $name): bool
	{
		$route = $this->getNamedRoute($name);

		if ($route !== null && isset($this->routes[$route->getIdentifier()])) {
			unset($this->routes[$route->getIdentifier()]);

			return true;
		}

		foreach ($this->groups as $group) {
			$result = $group->getRouteCollector()->removeNamedRoute($name);

			if ($result) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function lookupRoute(string $identifier, bool $throw = true): IRoute|null
	{
		if (isset($this->routes[$identifier])) {
			return $this->routes[$identifier];
		}

		foreach ($this->groups as $group) {
			$route = $group->getRouteCollector()->lookupRoute($identifier, false);

			if ($route !== null) {
				return $route;
			}
		}

		if ($throw) {
			throw new Exceptions\Runtime('Route not found, looks like your route cache is stale.');
		}

		return null;
	}

	#[Override]
	public function addMiddleware(MiddlewareInterface $middleware): void
	{
		$this->middleware[] = $middleware;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function get(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_GET], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function post(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_POST], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function put(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_PUT], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function patch(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_PATCH], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function delete(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_DELETE], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function options(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_OPTIONS], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function any(string $pattern, $callable): IRoute
	{
		return $this->map([
			RequestMethodInterface::METHOD_GET,
			RequestMethodInterface::METHOD_POST,
			RequestMethodInterface::METHOD_PUT,
			RequestMethodInterface::METHOD_PATCH,
			RequestMethodInterface::METHOD_DELETE,
			RequestMethodInterface::METHOD_OPTIONS,
		], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function map(array $methods, string $pattern, $handler): IRoute
	{
		$route = $this->createRoute($methods, $pattern, $handler);

		$this->routes[$route->getIdentifier()] = $route;

		return $route;
	}

	#[Override]
	public function group(string $pattern, callable $callable): IRouteGroup
	{
		$routeCollector = new self(
			$this->responseFactory,
			$this->controllerResolver,
			$this->routeParser,
			$this,
			$this->defaultInvocationHandler,
			$pattern,
		);

		$group = new RouteGroup($pattern, $routeCollector);

		$this->groups[] = $group;

		$callable($routeCollector);

		return $group;
	}

	#[Override]
	public function appendMiddlewareToDispatcher(Middleware\MiddlewareDispatcher $dispatcher): void
	{
		foreach ($this->middleware as $middleware) {
			$dispatcher->add($middleware);
		}

		if ($this->routeCollector !== null) {
			$this->routeCollector->appendMiddlewareToDispatcher($dispatcher);
		}
	}

	/**
	 * @param array<string> $methods
	 * @param callable|string|array<mixed> $callable
	 *
	 * @throws Exceptions\Runtime
	 */
	private function createRoute(array $methods, string $pattern, callable|string|array $callable): IRoute
	{
		return new Route(
			$methods,
			$pattern,
			$callable,
			$this,
			$this->responseFactory,
			$this->controllerResolver,
			$this->defaultInvocationHandler,
		);
	}

}
