<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Controllers;
use FastyBird\Core\Http\Middleware;
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use IteratorAggregate;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use RecursiveArrayIterator;
use function strtoupper;

/**
 * @phpstan-implements IteratorAggregate<int, Route>
 */
class Router implements IteratorAggregate
{

	public const string ROUTE = '__route__';

	public const string ROUTING_RESULTS = '__routingResults__';

	public const string BASE_PATH = '__basePath__';

	private string $basePath = '';

	private ResponseFactoryInterface $responseFactory;

	private RouteCollector $routeCollector;

	private RouteParser $routeParser;

	private Middleware\MiddlewareDispatcher $middlewareDispatcher;

	public function __construct(
		ResponseFactoryInterface|null $responseFactory = null,
		Controllers\ControllerResolver|null $controllerResolver = null,
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

		$this->middlewareDispatcher = new Middleware\MiddlewareDispatcher($routeHandler);
	}

	public function getBasePath(): string
	{
		return $this->basePath;
	}

	public function setBasePath(string $basePath): void
	{
		$this->basePath = $basePath;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getNamedRoute(string $name): Route|null
	{
		return $this->routeCollector->getNamedRoute($name);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function lookupRoute(string $identifier): Route
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
	 * Add GET route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function get(string $pattern, $callable): Route
	{
		return $this->map([RequestMethodInterface::METHOD_GET], $pattern, $callable);
	}

	/**
	 * Add POST route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function post(string $pattern, $callable): Route
	{
		return $this->map([RequestMethodInterface::METHOD_POST], $pattern, $callable);
	}

	/**
	 * Add PUT route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function put(string $pattern, $callable): Route
	{
		return $this->map([RequestMethodInterface::METHOD_PUT], $pattern, $callable);
	}

	/**
	 * Add PATCH route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function patch(string $pattern, $callable): Route
	{
		return $this->map([RequestMethodInterface::METHOD_PATCH], $pattern, $callable);
	}

	/**
	 * Add DELETE route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function delete(string $pattern, $callable): Route
	{
		return $this->map([RequestMethodInterface::METHOD_DELETE], $pattern, $callable);
	}

	/**
	 * Add OPTIONS route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function options(string $pattern, $callable): Route
	{
		return $this->map([RequestMethodInterface::METHOD_OPTIONS], $pattern, $callable);
	}

	/**
	 * Add route for any HTTP method
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function any(string $pattern, $callable): Route
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
	 * Add route with multiple methods
	 *
	 * @param array<string> $methods                 Numeric array of HTTP method names
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 *
	 * @throws Exceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function map(array $methods, string $pattern, $callable): Route
	{
		return $this->routeCollector->map($methods, $pattern, $callable);
	}

	/**
	 * Route Groups
	 *
	 * This method accepts a route pattern and a callback. All route
	 * declarations in the callback will be prepended by the group(s)
	 * that it is in.
	 */
	public function group(string $pattern, callable $callable): RouteGroup
	{
		return $this->routeCollector->group($pattern, $callable);
	}

	/**
	 * Build the path for a named route including the base path
	 *
	 * @param string $routeName    Route name
	 * @param array<mixed> $data        Named argument replacement data
	 * @param array<mixed> $queryParams Optional query string parameters
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	public function urlFor(string $routeName, array $data = [], array $queryParams = []): string
	{
		return $this->routeParser->urlFor($routeName, $data, $queryParams);
	}

	/**
	 * @throws InvalidArgumentException
	 */
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
	 * @return RecursiveArrayIterator<Route>
	 */
	#[Override]
	public function getIterator(): RecursiveArrayIterator
	{
		return new RecursiveArrayIterator($this->routeCollector->getRoutes());
	}

}
