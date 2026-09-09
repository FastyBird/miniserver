<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Cases\Unit\Connector;

use Error;
use FastyBird\Connector\Virtual\Connector;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Tests;
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
			Uuid\Uuid::fromString('93e760e1-f011-4a33-a70d-c9629706ccf8'),
			Documents\Connectors\Connector::class,
		);

		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);
		self::assertSame('93e760e1-f011-4a33-a70d-c9629706ccf8', $connector->getId()->toString());

		$connector = $factory->create($connector);

		self::assertFalse($connector->hasUnfinishedTasks());
	}

}
