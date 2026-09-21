<?php declare(strict_types = 1);

/**
 * IRouteCollector.php
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

use FastyBird\Core\Middleware\SlimRouter as Middleware;
use FastyBird\Core\Routing;
use Psr\Http\Server\MiddlewareInterface;

interface IRouteCollector
{

	public function setDefaultInvocationHandler(Routing\Handlers\IHandler $strategy): void;

	public function getPattern(): string;

	/**
	 * @return array<IRoute>
	 */
	public function getRoutes(): array;

	public function getNamedRoute(string $name, bool $throw = true): IRoute|null;

	/**
	 * @param string $name Route name
	 */
	public function removeNamedRoute(string $name): bool;

	public function lookupRoute(string $identifier, bool $throw = true): IRoute|null;

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
	 * Add route
	 *
	 * @param array<string> $methods                Array of HTTP methods
	 * @param string $pattern                  The route pattern
	 * @param callable|string|array<mixed> $handler The route callable
	 */
	public function map(array $methods, string $pattern, callable|string|array $handler): IRoute;

	/**
	 * Add route group
	 */
	public function group(string $pattern, callable $callable): IRouteGroup;

	public function appendMiddlewareToDispatcher(Middleware\MiddlewareDispatcher $dispatcher): void;

}
