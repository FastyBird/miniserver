<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Connector;

use Error;
use FastyBird\Connector\Zigbee2Mqtt\Connector;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConnectorFactoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testCreateConnector(): void
	{
		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$connector = $connectorsConfigurationRepository->find(
			Uuid\Uuid::fromString('f15d2072-fb60-421a-a85f-2566e4dc13fe'),
			Documents\Connectors\Connector::class,
		);

		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);
		self::assertSame('f15d2072-fb60-421a-a85f-2566e4dc13fe', $connector->getId()->toString());

		$connector = $factory->create($connector);

		self::assertFalse($connector->hasUnfinishedTasks());
	}

}
