<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Documents\Mapping\Driver\MappingDriverChain;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\WebSockets\Controllers as WebSocketsControllers;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Wamp;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Devices\Controllers as DevicesControllers;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Tests;
use Nette;
use Nette\Http;
use ReflectionException;
use ReflectionProperty;
use SplObjectStorage;

/**
 * The module reaches Core through DI tags, and a tag that one side stops producing, or that
 * the other side stops reading, is not an error: the tagged service is simply never used.
 * Each test here follows one tag from the service the module registers to the Core runtime
 * that is supposed to pick it up.
 */
final class TaggedServicesTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * WebSocketsExtension::ROUTES_TAG: the module's socket routes service is collected
	 * into the WAMP router
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testTaggedSocketRoutesReachTheWampRouter(): void
	{
		$router = $this->getContainer()->getByType(Wamp\WampRouter::class);

		$request = $router->match(
			new Handshake\Request(new Http\UrlScript('ws://localhost:8888/devices-module/v1/exchange')),
		);

		self::assertNotNull($request);
		self::assertSame('DevicesModule:Exchange', $request->getControllerName());
	}

	/**
	 * WebSocketsExtension::CONTROLLER_TAG: the controller factory creates a controller
	 * through its container service, found by the tag, and not as a new instance of its own
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidController
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 */
	public function testControllerFactoryCreatesTheTaggedControllerService(): void
	{
		$container = $this->getContainer();

		$serviceNames = $container->findByType(DevicesControllers\ExchangeV1::class);
		self::assertCount(1, $serviceNames);

		// The factory hands this instance out only if it asks the container for the tagged
		// service; its fallback, createInstance(), would build a different object.
		$controller = $container->createService($serviceNames[0]);
		self::assertInstanceOf(DevicesControllers\ExchangeV1::class, $controller);

		$container->addService($serviceNames[0], static fn (): DevicesControllers\ExchangeV1 => $controller);

		$factory = $container->getByType(WebSocketsControllers\ControllerFactory::class);

		self::assertSame($controller, $factory->createController('DevicesModule:Exchange'));
	}

	/**
	 * DocumentsExtension::DRIVER_TAG: the module adds its Documents directory to the tagged
	 * attribute driver, and that driver to the mapping chain, so its documents are mapped
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 */
	public function testModuleDocumentsAreMappedThroughTheDriverTag(): void
	{
		$chain = $this->getContainer()->getByType(MappingDriverChain::class);

		self::assertContains(Documents\Connectors\Generic::class, $chain->getAllClassNames());
	}

	/**
	 * ExchangeExtension::CONSUMER_STATE: a consumer the module tags with false is registered with
	 * the exchange consumer proxy, and registered disabled. The proxy offers no way to read a
	 * registration back, so its storage is read directly.
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 */
	public function testConsumerTaggedDisabledIsRegisteredDisabled(): void
	{
		$proxy = $this->getContainer()->getByType(ExchangeConsumers\Container::class);

		$storage = (new ReflectionProperty(ExchangeConsumers\Container::class, 'consumers'))->getValue($proxy);
		self::assertInstanceOf(SplObjectStorage::class, $storage);

		$enabled = [];

		foreach ($storage as $consumer) {
			$info = $storage[$consumer];
			self::assertInstanceOf(ExchangeConsumers\Info::class, $info);

			$enabled[$consumer::class] = $info->isEnabled();
		}

		self::assertArrayHasKey(DevicesConsumers\StatesActions::class, $enabled);
		self::assertFalse($enabled[DevicesConsumers\StatesActions::class]);
	}

}
