<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Wamp;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Handshake;
use Nette;
use Nette\Utils;
use Override;
use function array_keys;
use function assert;
use function is_array;
use function strlen;
use function strncmp;
use function substr;

/**
 * WebSockets routes list
 */
final class RouteList extends Utils\ArrayList implements WampRouter
{

	private array $cachedRoutes;

	private string $module;

	public function __construct(string|null $module = null)
	{
		$this->module = $module ? $module . ':' : '';
	}

	/**
	 * Maps HTTP request to a application Request object
	 */
	#[Override]
	public function match(Handshake\IRequest $httpRequest): Controllers\Request|null
	{
		foreach ($this as $route) {
			assert($route instanceof WampRouter);
			$appRequest = $route->match($httpRequest);

			if ($appRequest !== null) {
				$name = $appRequest->getControllerName();

				if (strncmp($name, 'IPub:', 5)) {
					$appRequest->setControllerName($this->module . $name);
				}

				return $appRequest;
			}
		}

		return null;
	}

	/**
	 * Constructs absolute URL from Request object
	 */
	#[Override]
	public function constructUrl(Controllers\DispatchRequest $appRequest): string|null
	{
		if ($this->cachedRoutes === null) {
			$this->warmupCache();
		}

		if ($this->module) {
			if (strncmp($tmp = $appRequest->getControllerName(), $this->module, strlen($this->module)) === 0) {
				$appRequest = clone $appRequest;
				$appRequest->setControllerName(substr($tmp, strlen($this->module)));

			} else {
				return null;
			}
		}

		$controller = $appRequest->getControllerName();

		if (!isset($this->cachedRoutes[$controller])) {
			$controller = '*';
		}

		foreach ($this->cachedRoutes[$controller] as $route) {
			assert($route instanceof WampRouter);
			$url = $route->constructUrl($appRequest);

			if ($url !== null) {
				return $url;
			}
		}

		return null;
	}

	/**
	 * Adds the router
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 */
	#[Override]
	public function offsetSet(mixed $index, mixed $route): void
	{
		if (!$route instanceof WampRouter) {
			throw new Exceptions\InvalidArgument('Argument must be IWampRouter descendant.');
		}

		parent::offsetSet($index, $route);
	}

	public function getModule(): string
	{
		return $this->module;
	}

	public function warmupCache(): void
	{
		$routes = [];
		$routes['*'] = [];

		foreach ($this as $route) {
			$controllers = $route instanceof WampRoute && is_array($tmp = $route->getTargetControllers())
				? $tmp
				: array_keys($routes);

			foreach ($controllers as $controller) {
				if (!isset($routes[$controller])) {
					$routes[$controller] = $routes['*'];
				}

				$routes[$controller][] = $route;
			}
		}

		$this->cachedRoutes = $routes;
	}

}
