<?php declare(strict_types = 1);

/**
 * Validator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           11.01.22
 */

namespace FastyBird\Module\Ui\Router;

use Exception;
use FastRoute;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser\Std;
use FastyBird\Core\Http\Routing;
use Fig\Http\Message\RequestMethodInterface;
use Nette\DI;
use function assert;

/**
 * Route validator
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Validator
{

	private Routing\FastRouteDispatcher|null $routerDispatcher = null;

	public function __construct(private readonly DI\Container $container)
	{
	}

	/**
	 * @throws Exception
	 */
	public function validate(string $link, string $method = RequestMethodInterface::METHOD_GET): bool
	{
		$results = $this->getRouterDispatcher()->dispatch($method, $link);

		return $results[0] === Routing\RoutingResults::FOUND;
	}

	/**
	 * @throws Exception
	 */
	private function getRouterDispatcher(): Routing\FastRouteDispatcher
	{
		if ($this->routerDispatcher !== null) {
			return $this->routerDispatcher;
		}

		$router = $this->container->getByType(Routing\IRouter::class);

		$routeDefinitionCallback = static function (FastRouteCollector $r) use ($router): void {
			$basePath = $router->getBasePath();

			foreach ($router->getIterator() as $route) {
				$r->addRoute($route->getMethods(), $basePath . $route->getPattern(), $route->getIdentifier());
			}
		};

		$dispatcher = FastRoute\simpleDispatcher($routeDefinitionCallback, [
			'dispatcher' => Routing\FastRouteDispatcher::class,
			'routeParser' => new Std(),
		]);
		assert($dispatcher instanceof Routing\FastRouteDispatcher);

		$this->routerDispatcher = $dispatcher;

		return $this->routerDispatcher;
	}

}
