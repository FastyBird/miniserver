<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Router;

use FastyBird\Library\WebSockets\Application;
use FastyBird\Library\WebSockets\Exceptions;
use FastyBird\Library\WebSockets\Http;
use Nette\Utils;
use function array_keys;
use function assert;
use function is_array;
use function strlen;
use function strncmp;
use function substr;

/**
 * WebSockets routes list
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @author         David Grudl (https://davidgrudl.com)
 */
class RouteList extends Utils\ArrayList implements IRouter
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
	public function match(Http\IRequest $httpRequest): Application\Request|null
	{
		foreach ($this as $route) {
			assert($route instanceof IRouter);
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
	public function constructUrl(Application\IRequest $appRequest): string|null
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
			assert($route instanceof IRouter);
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
	 */
	public function offsetSet(mixed $index, mixed $route): void
	{
		if (!$route instanceof IRouter) {
			throw new Exceptions\InvalidArgument('Argument must be IRouter descendant.');
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
			$controllers = $route instanceof Route && is_array($tmp = $route->getTargetControllers())
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
