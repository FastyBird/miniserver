<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\Connector;

use Error;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Tests;
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
			Uuid\Uuid::fromString('37b86cdc-376b-4d4c-9683-aa4f41daa13a'),
			Documents\Connectors\Connector::class,
		);

		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);
		self::assertSame('37b86cdc-376b-4d4c-9683-aa4f41daa13a', $connector->getId()->toString());

		$connector = $factory->create($connector);

		self::assertFalse($connector->hasUnfinishedTasks());
	}

}
