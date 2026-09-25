<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Commands;
use FastyBird\Connector\Shelly\Connector;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Hydrators;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Schemas;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Subscribers;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\Core\Exceptions;
use Nette;

final class ShellyExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertCount(2, $container->findByType(Writers\WriterFactory::class));

		self::assertNotNull($container->getByType(Clients\LocalFactory::class, false));
		self::assertNotNull($container->getByType(Clients\CloudFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DiscoveryFactory::class, false));

		self::assertNotNull($container->getByType(Services\HttpClientFactory::class, false));
		self::assertNotNull($container->getByType(Services\MulticastFactory::class, false));

		self::assertNotNull($container->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($container->getByType(API\Gen1CoapFactory::class, false));
		self::assertNotNull($container->getByType(API\Gen1HttpApiFactory::class, false));
		self::assertNotNull($container->getByType(API\Gen2HttpApiFactory::class, false));
		self::assertNotNull($container->getByType(API\Gen2WsApiFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers\StoreLocalDevice::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceConnectionState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\StoreDeviceState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers\WriteChannelPropertyState::class, false));
		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Device::class, false));

		self::assertNotNull($container->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Device::class, false));

		self::assertNotNull($container->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($container->getByType(Helpers\Connector::class, false));
		self::assertNotNull($container->getByType(Helpers\Device::class, false));
		self::assertNotNull($container->getByType(Helpers\Loader::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Discover::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
