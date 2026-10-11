<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Connector;

use BadMethodCallException;
use Doctrine\DBAL;
use Error;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Connector;
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use InvalidArgumentException;
use Nette;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use ReflectionProperty;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConnectorTest extends Tests\Cases\Unit\DbTestCase
{

	private const string LAN_FACTORY_REACHED = 'The auto client asked for its LAN client';

	/**
	 * @throws BadMethodCallException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DBAL\Exception
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testExecuteInAutoModeBuildsAutoClient(): void
	{
		$this->getDb()->executeStatement(
			"UPDATE fb_devices_module_connectors_properties SET property_value = 'auto' "
			. "WHERE connector_id = UNHEX('63a28696cbea475784d7156e52ade4d3') AND property_identifier = 'mode'",
		);

		// The auto client connects its LAN client first. Stopping there keeps the test off the network,
		// and it proves the connector got as far as connecting the client it built.
		$lanFactory = new class (self::LAN_FACTORY_REACHED) implements Clients\LanFactory
		{

			public function __construct(private readonly string $message)
			{
			}

			/**
			 * @throws SonoffExceptions\InvalidState
			 */
			#[Override]
			public function create(
				Documents\Connectors\Connector $connector,
				bool $autoMode = false,
			): Clients\Lan
			{
				throw new SonoffExceptions\InvalidState($this->message);
			}

		};

		$this->mockContainerService(Clients\LanFactory::class, $lanFactory);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$connectorDocument = $connectorsConfigurationRepository->find(
			Uuid\Uuid::fromString('63a28696-cbea-4757-84d7-156e52ade4d3'),
			Documents\Connectors\Connector::class,
		);

		self::assertInstanceOf(Documents\Connectors\Connector::class, $connectorDocument);

		$connector = $factory->create($connectorDocument);

		$reached = false;

		try {
			$connector->execute();
		} catch (SonoffExceptions\InvalidState $ex) {
			self::assertSame(self::LAN_FACTORY_REACHED, $ex->getMessage());

			$reached = true;
		}

		self::assertTrue($reached);

		$client = (new ReflectionProperty(Connector\Connector::class, 'client'))->getValue($connector);

		self::assertInstanceOf(Clients\Auto::class, $client);
	}

}
