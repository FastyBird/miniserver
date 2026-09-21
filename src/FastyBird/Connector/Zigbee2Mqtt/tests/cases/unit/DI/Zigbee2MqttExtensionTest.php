<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Commands;
use FastyBird\Connector\Zigbee2Mqtt\Connector;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Hydrators;
use FastyBird\Connector\Zigbee2Mqtt\Models;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
use FastyBird\Connector\Zigbee2Mqtt\Subscribers;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Connector\Zigbee2Mqtt\Writers;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use Nette;

final class Zigbee2MqttExtensionTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		$container = $this->createContainer();

		self::assertCount(2, $container->findByType(Writers\WriterFactory::class));

		self::assertNotNull($container->getByType(API\ConnectionManager::class, false));
		self::assertNotNull($container->getByType(API\ClientFactory::class, false));

		self::assertNotNull($container->getByType(Clients\MqttFactory::class, false));
		self::assertNotNull($container->getByType(Clients\DiscoveryFactory::class, false));
		self::assertNotNull($container->getByType(Clients\Subscribers\BridgeFactory::class, false));
		self::assertNotNull($container->getByType(Clients\Subscribers\DeviceFactory::class, false));

		self::assertNotNull($container->getByType(Queue\Consumers::class, false));
		self::assertNotNull($container->getByType(Queue\Queue::class, false));

		self::assertNotNull($container->getByType(Subscribers\Properties::class, false));
		self::assertNotNull($container->getByType(Subscribers\Controls::class, false));

		self::assertNotNull($container->getByType(Schemas\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\Bridge::class, false));
		self::assertNotNull($container->getByType(Schemas\Devices\SubDevice::class, false));

		self::assertNotNull($container->getByType(Hydrators\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\Bridge::class, false));
		self::assertNotNull($container->getByType(Hydrators\Devices\SubDevice::class, false));

		self::assertNotNull($container->getByType(Models\StateRepository::class, false));

		self::assertNotNull($container->getByType(Helpers\MessageBuilder::class, false));
		self::assertNotNull($container->getByType(Helpers\Connectors\Connector::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\Bridge::class, false));
		self::assertNotNull($container->getByType(Helpers\Devices\SubDevice::class, false));

		self::assertNotNull($container->getByType(Commands\Execute::class, false));
		self::assertNotNull($container->getByType(Commands\Install::class, false));

		self::assertNotNull($container->getByType(Connector\ConnectorFactory::class, false));
	}

}
