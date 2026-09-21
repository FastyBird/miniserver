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

namespace FastyBird\Core\Routing\SlimRouter;

use FastRoute;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser\Std;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as SlimRouterExceptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function assert;
use function rawurldecode;

class RouteHandler implements RequestHandlerInterface
{

	private FastRouteDispatcher|null $dispatcher = null;

	public function __construct(private IRouter $router)
	{
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws SlimRouterExceptions\HttpMethodNotAllowed
	 * @throws SlimRouterExceptions\HttpNotFound
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		// If routing hasn't been done, then do it now so we can dispatch
		if ($request->getAttribute(Router::ROUTING_RESULTS) === null) {
			$request = $this->performRouting($request);
		}

		$request = $request->withAttribute(
			Router::BASE_PATH,
			$this->router->getBasePath(),
		);

		$route = $request->getAttribute(Router::ROUTE);
		assert($route instanceof IRoute);

		return $route->run($request);
	}

	/**
	 * @throws SlimRouterExceptions\HttpMethodNotAllowed
	 * @throws SlimRouterExceptions\HttpNotFound
	 */
	public function performRouting(ServerRequestInterface $request): ServerRequestInterface
	{
		$routingResults = $this->computeRoutingResults(
			$request->getUri()->getPath(),
			$request->getMethod(),
		);

		$routeStatus = $routingResults->getRouteStatus();

		$request = $request->withAttribute(Router::ROUTING_RESULTS, $routingResults);

		switch ($routeStatus) {
			case RoutingResults::FOUND:
				$routeArguments = $routingResults->getRouteArguments();
				$routeIdentifier = $routingResults->getRouteIdentifier() ?? '';

				$route = $this->router->lookupRoute($routeIdentifier);
				$route->prepare($routeArguments);

				return $request->withAttribute(Router::ROUTE, $route);
			case RoutingResults::NOT_FOUND:
				throw new SlimRouterExceptions\HttpNotFound($request);
			case RoutingResults::METHOD_NOT_ALLOWED:
				$exception = new SlimRouterExceptions\HttpMethodNotAllowed($request);
				$exception->setAllowedMethods($this->getDispatcher()->getAllowedMethods($request->getUri()->getPath()));

				throw $exception;
			default:
				throw new Exceptions\Runtime('An unexpected error occurred while performing routing.');
		}
	}

	/**
	 * @param string $uri Should be $request->getUri()->getPath()
	 */
	private function computeRoutingResults(string $uri, string $method): RoutingResults
	{
		$uri = rawurldecode($uri);

		if ($uri === '' || $uri[0] !== '/') {
			$uri = '/' . $uri;
		}

		$dispatcher = $this->getDispatcher();

		$results = $dispatcher->dispatch($method, $uri);

		return new RoutingResults($method, $uri, $results[0], $results[1], $results[2]);
	}

	private function getDispatcher(): FastRouteDispatcher
	{
		if ($this->dispatcher !== null) {
			return $this->dispatcher;
		}

		$routeDefinitionCallback = function (FastRouteCollector $r): void {
			$basePath = $this->router->getBasePath();

			foreach ($this->router->getIterator() as $route) {
				assert($route instanceof IRoute);
				$r->addRoute($route->getMethods(), $basePath . $route->getPattern(), $route->getIdentifier());
			}
		};

		$dispatcher = FastRoute\simpleDispatcher($routeDefinitionCallback, [
			'dispatcher' => FastRouteDispatcher::class,
			'routeParser' => new Std(),
		]);
		assert($dispatcher instanceof FastRouteDispatcher);

		$this->dispatcher = $dispatcher;

		return $this->dispatcher;
	}

}
