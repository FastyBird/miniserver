<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Router;

use Exception;
use FastRoute;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use FastyBird\Core\Http\Routing;
use Fig\Http\Message\RequestMethodInterface;
use function assert;

/**
 * Route validator
 */
final class Validator
{

	private Routing\FastRouteDispatcher|null $routerDispatcher = null;

	public function __construct(private readonly Routing\Router $router)
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

		$router = $this->router;

		$routeDefinitionCallback = static function (RouteCollector $r) use ($router): void {
			$basePath = $router->basePath;

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
