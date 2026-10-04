<?php declare(strict_types = 1);

namespace FastyBird\MiniServer\Tests\Cases\Application;

use Error;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Characterization, at production scope, of the modules' WAMP routes resolved through the WAMP
 * router and the controller factory (#460 §1.5, §3.5).
 *
 * Devices and Ui each contribute a route list through WebSocketsExtension::ROUTES_TAG and a
 * controller mapping; the router lists them in tag order, the route names the controller, and
 * ControllerFactory -- which keeps Nette\DI\Container on purpose, to find the tagged controller
 * service and create it -- maps the name to the class. E5.5 (#637) collapses the router and
 * factory interfaces and E5.7 (#639) changes how services are located; neither may change any
 * of this.
 */
final class WampModuleRouteTest extends TestCase
{

	use ProductionProbe;

	/**
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testEachModuleExchangeRouteResolvesToItsTaggedController(): void
	{
		self::assertSame(
			[
				'routers' => [
					['/devices-module/v1/exchange'],
					['/ui-module/v1/exchange'],
				],
				'routes' => [
					'/devices-module/v1/exchange' => [
						'controllerName' => 'DevicesModule:Exchange',
						'parameters' => [],
						'class' => 'FastyBird\Module\Devices\Controllers\ExchangeV1',
						'created' => 'FastyBird\Module\Devices\Controllers\ExchangeV1',
						'tagged' => true,
					],
					'/ui-module/v1/exchange' => [
						'controllerName' => 'UiModule:Exchange',
						'parameters' => [],
						'class' => 'FastyBird\Module\Ui\Controllers\ExchangeV1',
						'created' => 'FastyBird\Module\Ui\Controllers\ExchangeV1',
						'tagged' => true,
					],
				],
			],
			$this->probe('wamp-module-routes'),
		);
	}

	/**
	 * The WAMP link generator, fetched from the compiled container by type, asked for the link
	 * each module's SocketsBridge publishes exchange messages under (census T12-10; E5.5 #637
	 * moves the class). Each link is the topic its module's frontend subscribes to. A
	 * destination with no controller fails the documented way, with InvalidLink.
	 *
	 * Until #625 this threw for every routed destination: RouteList::$cachedRoutes was a typed
	 * property with no default, read before warmupCache() ever assigned it.
	 *
	 * @throws Error
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	public function testTheLinkGeneratorLinksEachModuleExchangeToItsTopic(): void
	{
		self::assertSame(
			[
				'DevicesModule:Exchange:' => '/devices-module/v1/exchange',
				'UiModule:Exchange:' => '/ui-module/v1/exchange',
				'E5Probe:Missing:' => 'FastyBird\Core\Exceptions\InvalidLink: Cannot load controller "E5Probe:Missing",'
					. ' class "E5ProbeModule\MissingController" was not found.',
			],
			$this->probe('wamp-links'),
		);
	}

}
