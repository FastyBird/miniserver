<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use FastRoute;
use FastRoute\RouteCollector as FastRouteCollector;
use FastRoute\RouteParser\Std;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Exceptions as HttpExceptions;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function assert;
use function rawurldecode;

final class RouteHandler implements RequestHandlerInterface
{

	private FastRouteDispatcher|null $dispatcher = null;

	public function __construct(private IRouter $router)
	{
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws CoreExceptions\Runtime
	 * @throws HttpExceptions\HttpMethodNotAllowed
	 * @throws HttpExceptions\HttpNotFound
	 */
	#[Override]
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
	 * @throws CoreExceptions\Runtime
	 * @throws HttpExceptions\HttpMethodNotAllowed
	 * @throws HttpExceptions\HttpNotFound
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
				throw new HttpExceptions\HttpNotFound($request);
			case RoutingResults::METHOD_NOT_ALLOWED:
				$exception = new HttpExceptions\HttpMethodNotAllowed($request);
				$exception->setAllowedMethods($this->getDispatcher()->getAllowedMethods($request->getUri()->getPath()));

				throw $exception;
			default:
				throw new CoreExceptions\Runtime('An unexpected error occurred while performing routing.');
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
