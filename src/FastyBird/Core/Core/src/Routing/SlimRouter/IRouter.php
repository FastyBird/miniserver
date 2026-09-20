<?php declare(strict_types = 1);

/**
 * IRouter.php
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

namespace FastyBird\Core\Routing\SlimRouter;

use IteratorAggregate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @phpstan-extends IteratorAggregate<int, IRoute>
 */
interface IRouter extends IteratorAggregate
{

	public function getBasePath(): string;

	public function setBasePath(string $basePath): void;

	public function getNamedRoute(string $name): IRoute|null;

	public function lookupRoute(string $identifier): IRoute;

	public function addMiddleware(MiddlewareInterface $middleware): void;

	/**
	 * Add GET route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function get(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add POST route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function post(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add PUT route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function put(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add PATCH route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function patch(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add DELETE route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function delete(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add OPTIONS route
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function options(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add route for any HTTP method
	 *
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function any(string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Add route with multiple methods
	 *
	 * @param array<string> $methods                 Numeric array of HTTP method names
	 * @param string $pattern                   The route URI pattern
	 * @param callable|string|array<mixed> $callable The route callback routine
	 */
	public function map(array $methods, string $pattern, callable|string|array $callable): IRoute;

	/**
	 * Route Groups
	 *
	 * This method accepts a route pattern and a callback. All route
	 * declarations in the callback will be prepended by the group(s)
	 * that it is in.
	 */
	public function group(string $pattern, callable $callable): IRouteGroup;

	/**
	 * Build the path for a named route including the base path
	 *
	 * @param string $routeName    Route name
	 * @param array<mixed> $data        Named argument replacement data
	 * @param array<mixed> $queryParams Optional query string parameters
	 */
	public function urlFor(string $routeName, array $data = [], array $queryParams = []): string;

	public function handle(ServerRequestInterface $request): ResponseInterface;

}
