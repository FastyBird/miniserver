<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Routing;

use FastyBird\Core\Controllers\SlimRouter\ControllerResolver;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\ResponseFactory;
use FastyBird\Core\Routing\IRouteCollector;
use FastyBird\Core\Routing\RouteCollector;
use FastyBird\Core\Routing\RouteParser;
use FastyBird\Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouteParserTest extends TestCase
{

	/**
	 * @throws Exceptions\Runtime
	 */
	public function testGetNamedRouteRetrievesARouteMappedAndNamed(): void
	{
		$router = new Router();

		$route = $router->get('/api/v1/devices', static function (): void {
		});
		$route->setName('devices.index');

		self::assertSame($route, $router->getNamedRoute('devices.index'));
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testUrlForReturnsThePlainPatternForANamedRoute(): void
	{
		$router = new Router();

		$router->get('/api/v1/devices', static function (): void {
		})->setName('devices.index');

		self::assertSame('/api/v1/devices', $router->urlFor('devices.index'));
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testUrlForSubstitutesAPlaceholderArgument(): void
	{
		$router = new Router();

		$router->get('/api/v1/devices/{id}', static function (): void {
		})->setName('devices.read');

		self::assertSame(
			'/api/v1/devices/9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			$router->urlFor('devices.read', ['id' => '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a']),
		);
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testUrlForAppendsQueryStringArguments(): void
	{
		$router = new Router();

		$router->get('/api/v1/devices', static function (): void {
		})->setName('devices.index');

		self::assertSame(
			'/api/v1/devices?foo=bar',
			$router->urlFor('devices.index', [], ['foo' => 'bar']),
		);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function testGetNamedRouteOnUnknownNameThrowsRuntime(): void
	{
		$router = new Router();

		self::expectException(Exceptions\Runtime::class);

		$router->getNamedRoute('unknown.route');
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function testRemoveNamedRouteMakesTheNameUnresolvable(): void
	{
		$responseFactory = new ResponseFactory();
		$router = new Router($responseFactory);
		$routeParser = new RouteParser($router);
		$collector = new RouteCollector($responseFactory, new ControllerResolver(), $routeParser);

		$route = $collector->get('/api/v1/devices', static function (): void {
		});
		$route->setName('devices.index');

		self::assertSame($route, $collector->getNamedRoute('devices.index'));

		self::assertTrue($collector->removeNamedRoute('devices.index'));

		self::expectException(Exceptions\Runtime::class);

		$collector->getNamedRoute('devices.index');
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function testGroupPrefixesThePatternsOfRoutesDeclaredInsideIt(): void
	{
		$router = new Router();

		$router->group('/api/v1', static function (IRouteCollector $group): void {
			$group->get('/devices', static function (): void {
			})->setName('devices.index');
		});

		$route = $router->getNamedRoute('devices.index');

		self::assertNotNull($route);
		self::assertSame('/api/v1/devices', $route->getPattern());
	}

	/**
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 */
	public function testSetBasePathIsReflectedInUrlForOutput(): void
	{
		$router = new Router();

		$router->get('/api/v1/devices', static function (): void {
		})->setName('devices.index');

		$router->setBasePath('/sub');

		self::assertSame('/sub/api/v1/devices', $router->urlFor('devices.index'));
	}

}
