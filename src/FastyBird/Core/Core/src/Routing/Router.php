<?php declare(strict_types = 1);

/**
 * Router.php
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

use FastyBird\Core\Controllers\SlimRouter as Controllers;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Middleware\SlimRouter\IMiddlewareDispatcher;
use FastyBird\Core\Middleware\SlimRouter\MiddlewareDispatcher;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use RecursiveArrayIterator;
use function strtoupper;

class Router implements IRouter
{

	public const ROUTE = '__route__';

	public const ROUTING_RESULTS = '__routingResults__';

	public const BASE_PATH = '__basePath__';

	private string $basePath = '';

	private ResponseFactoryInterface $responseFactory;

	private IRouteCollector $routeCollector;

	private IRouteParser $routeParser;

	private IMiddlewareDispatcher $middlewareDispatcher;

	public function __construct(
		ResponseFactoryInterface|null $responseFactory = null,
		Controllers\IControllerResolver|null $controllerResolver = null,
	)
	{
		$this->responseFactory = $responseFactory ?? new Http\ResponseFactory();
		$this->routeParser = new RouteParser($this);

		$this->routeCollector = new RouteCollector(
			$this->responseFactory,
			$controllerResolver ?? new Controllers\ControllerResolver(),
			$this->routeParser,
		);

		$routeHandler = new RouteHandler($this);

		$this->middlewareDispatcher = new MiddlewareDispatcher($routeHandler);
	}

	public function getBasePath(): string
	{
		return $this->basePath;
	}

	public function setBasePath(string $basePath): void
	{
		$this->basePath = $basePath;
	}

	public function getNamedRoute(string $name): IRoute|null
	{
		return $this->routeCollector->getNamedRoute($name);
	}

	public function lookupRoute(string $identifier): IRoute
	{
		$route = $this->routeCollector->lookupRoute($identifier);

		if ($route === null) {
			throw new Exceptions\Runtime('Route not found, looks like your route cache is stale.');
		}

		return $route;
	}

	public function addMiddleware(MiddlewareInterface $middleware): void
	{
		$this->middlewareDispatcher->add($middleware);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_GET], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function post(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_POST], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function put(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_PUT], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function patch(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_PATCH], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_DELETE], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function options(string $pattern, $callable): IRoute
	{
		return $this->map([RequestMethodInterface::METHOD_OPTIONS], $pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
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
	 */
	public function map(array $methods, string $pattern, $callable): IRoute
	{
		return $this->routeCollector->map($methods, $pattern, $callable);
	}

	public function group(string $pattern, callable $callable): IRouteGroup
	{
		return $this->routeCollector->group($pattern, $callable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function urlFor(string $routeName, array $data = [], array $queryParams = []): string
	{
		return $this->routeParser->urlFor($routeName, $data, $queryParams);
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$response = $this->middlewareDispatcher->handle($request);

		/**
		 * This is to be in compliance with RFC 2616, Section 9.
		 * If the incoming request method is HEAD, we need to ensure that the response body
		 * is empty as the request may fall back on a GET route handler due to FastRoute's
		 * routing logic which could potentially append content to the response body
		 * https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
		 */
		$method = strtoupper($request->getMethod());

		if ($method === RequestMethodInterface::METHOD_HEAD) {
			$emptyBody = $this->responseFactory->createResponse()->getBody();

			return $response->withBody($emptyBody);
		}

		return $response;
	}

	/**
	 * @return RecursiveArrayIterator<IRoute>
	 */
	public function getIterator(): RecursiveArrayIterator
	{
		return new RecursiveArrayIterator($this->routeCollector->getRoutes());
	}

}
