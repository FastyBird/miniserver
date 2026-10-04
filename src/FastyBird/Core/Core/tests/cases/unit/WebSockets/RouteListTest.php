<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\WebSockets;

use FastyBird\Core\Exceptions;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Wamp;
use Nette;
use PHPUnit\Framework\TestCase;

/**
 * RouteList::constructUrl() builds its per-controller route cache lazily, on first use, and a route
 * added afterwards drops it (#625). The production WAMP router is a RouteList of module RouteLists,
 * one per WebSocketsExtension::ROUTES_TAG service, and LinkGenerator::link() reaches every module
 * route through it.
 */
final class RouteListTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 */
	public function testTheFirstUrlIsConstructedFromALazilyWarmedCache(): void
	{
		$routes = new Wamp\RouteList();
		$routes[] = new Wamp\WampRoute('/probe-module/v1/exchange', 'ProbeModule:Exchange:');

		self::assertSame(
			'/probe-module/v1/exchange',
			$routes->constructUrl(new Controllers\Request('ProbeModule:Exchange')),
		);
		self::assertNull($routes->constructUrl(new Controllers\Request('OtherModule:Exchange')));
	}

	/**
	 * The production shape: a root list holding one list per module, each resolved to its own route.
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 */
	public function testEachModuleListOfANestedRouterConstructsItsOwnUrl(): void
	{
		$devices = new Wamp\RouteList();
		$devices[] = new Wamp\WampRoute('/devices-module/v1/exchange', 'DevicesModule:Exchange:');

		$ui = new Wamp\RouteList();
		$ui[] = new Wamp\WampRoute('/ui-module/v1/exchange', 'UiModule:Exchange:');

		$router = new Wamp\RouteList();
		$router[] = $devices;
		$router[] = $ui;

		self::assertSame(
			'/devices-module/v1/exchange',
			$router->constructUrl(new Controllers\Request('DevicesModule:Exchange')),
		);
		self::assertSame(
			'/ui-module/v1/exchange',
			$router->constructUrl(new Controllers\Request('UiModule:Exchange')),
		);
		self::assertNull($router->constructUrl(new Controllers\Request('OtherModule:Exchange')));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\OutOfRangeException
	 */
	public function testARouteAddedAfterTheCacheIsWarmIsLinkedToo(): void
	{
		$routes = new Wamp\RouteList();
		$routes[] = new Wamp\WampRoute('/devices-module/v1/exchange', 'DevicesModule:Exchange:');

		self::assertSame(
			'/devices-module/v1/exchange',
			$routes->constructUrl(new Controllers\Request('DevicesModule:Exchange')),
		);

		$routes[] = new Wamp\WampRoute('/ui-module/v1/exchange', 'UiModule:Exchange:');

		self::assertSame(
			'/ui-module/v1/exchange',
			$routes->constructUrl(new Controllers\Request('UiModule:Exchange')),
		);
	}

}
