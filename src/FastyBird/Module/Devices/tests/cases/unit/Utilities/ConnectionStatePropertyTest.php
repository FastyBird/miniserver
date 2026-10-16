<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Utilities;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use FastyBird\Module\Devices\Utilities;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Throwable;
use function array_diff_key;
use function array_flip;

/**
 * A connector or a device without a connection state property gets one the first time its state
 * is set. These pin the property the utilities create and the state they store, the two values
 * they hand to Devices' managers (#723).
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConnectionStatePropertyTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	/** @var list<array<mixed>> */
	private array $stored = [];

	/**
	 * @throws Throwable
	 */
	public function testAConnectorWithoutAStatePropertyGetsOne(): void
	{
		$this->recordStates();

		$connector = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class)->find(
			Uuid\Uuid::fromString(self::CONNECTOR),
		);
		self::assertNotNull($connector);

		$this->getContainer()->getByType(Utilities\ConnectorConnection::class)->setState(
			$connector,
			Types\ConnectionState::RUNNING,
			Sources\Connector::VIRTUAL,
		);

		$findProperty = new Queries\Entities\FindConnectorProperties();
		$findProperty->byConnectorId(Uuid\Uuid::fromString(self::CONNECTOR));
		$findProperty->byIdentifier(Types\ConnectorPropertyIdentifier::STATE->value);

		$property = $this->getContainer()->getByType(
			Models\Entities\Connectors\Properties\PropertiesRepository::class,
		)->findOneBy($findProperty);

		self::assertInstanceOf(Entities\Connectors\Properties\Dynamic::class, $property);
		self::assertSame(
			[
				'type' => 'dynamic',
				'category' => 'generic',
				'identifier' => 'state',
				'name' => null,
				'data_type' => 'enum',
				'unit' => null,
				'format' => ['running', 'stopped', 'unknown', 'sleeping', 'alert'],
				'invalid' => null,
				'scale' => null,
				'step' => null,
				'default' => null,
				'value_transformer' => null,
				'settable' => false,
				'queryable' => false,
				'connector' => self::CONNECTOR,
				'owner' => null,
			],
			$this->withoutGenerated($property->toArray()),
		);
		self::assertSame(
			[
				[
					States\Property::ACTUAL_VALUE_FIELD => 'running',
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => false,
				],
			],
			$this->stored,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testADeviceWithoutAStatePropertyGetsOne(): void
	{
		$this->recordStates();

		$device = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class)->find(
			Uuid\Uuid::fromString(self::DEVICE),
		);
		self::assertNotNull($device);

		$this->getContainer()->getByType(Utilities\DeviceConnection::class)->setState(
			$device,
			Types\ConnectionState::CONNECTED,
			Sources\Connector::VIRTUAL,
		);

		$findProperty = new Queries\Entities\FindDeviceProperties();
		$findProperty->byDeviceId(Uuid\Uuid::fromString(self::DEVICE));
		$findProperty->byIdentifier(Types\DevicePropertyIdentifier::STATE->value);

		$property = $this->getContainer()->getByType(
			Models\Entities\Devices\Properties\PropertiesRepository::class,
		)->findOneBy($findProperty);

		self::assertInstanceOf(Entities\Devices\Properties\Dynamic::class, $property);
		self::assertSame(
			[
				'type' => 'dynamic',
				'category' => 'generic',
				'identifier' => 'state',
				'name' => null,
				'data_type' => 'enum',
				'unit' => null,
				'format' => ['connected', 'disconnected', 'running', 'sleeping', 'stopped', 'lost', 'alert', 'unknown'],
				'invalid' => null,
				'scale' => null,
				'step' => null,
				'default' => null,
				'value_transformer' => null,
				'settable' => false,
				'queryable' => false,
				'device' => self::DEVICE,
				'owner' => null,
				'children' => [],
			],
			$this->withoutGenerated($property->toArray()),
		);
		self::assertSame(
			[
				[
					States\Property::ACTUAL_VALUE_FIELD => 'connected',
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => false,
				],
			],
			$this->stored,
		);
	}

	/**
	 * @param array<string, mixed> $property
	 *
	 * @return array<string, mixed>
	 */
	private function withoutGenerated(array $property): array
	{
		return array_diff_key($property, array_flip(['id', 'created_at', 'updated_at']));
	}

	/**
	 * Stands for a state storage that records what is stored in it, as a plain array
	 *
	 * @throws Throwable
	 */
	private function recordStates(): void
	{
		$caching = $this->getContainer()->getByType(DevicesCaching\Container::class);

		$connectorStorage = $this->createMock(Models\States\Connectors\Writer::class);
		$connectorStorage->method('create')->willReturnCallback(
			function (Uuid\UuidInterface $id, array $values): States\ConnectorProperty {
				$this->stored[] = $values;

				return new Tests\Fixtures\Dummy\ConnectorPropertyState($id, null, null, false, true);
			},
		);

		$connectorLookup = $this->createMock(Models\States\Connectors\Reader::class);
		$connectorLookup->method('find')->willReturn(null);

		$this->mockContainerService(
			Models\States\Connectors\Manager::class,
			new Models\States\Connectors\Manager($connectorStorage),
		);
		$this->mockContainerService(
			Models\States\Connectors\Repository::class,
			new Models\States\Connectors\Repository($caching, $connectorLookup),
		);

		$deviceStorage = $this->createMock(Models\States\Devices\Writer::class);
		$deviceStorage->method('create')->willReturnCallback(
			function (Uuid\UuidInterface $id, array $values): States\DeviceProperty {
				$this->stored[] = $values;

				return new Tests\Fixtures\Dummy\DevicePropertyState($id, null, null, false, true);
			},
		);

		$deviceLookup = $this->createMock(Models\States\Devices\Reader::class);
		$deviceLookup->method('find')->willReturn(null);

		$this->mockContainerService(
			Models\States\Devices\Manager::class,
			new Models\States\Devices\Manager($deviceStorage),
		);
		$this->mockContainerService(
			Models\States\Devices\Repository::class,
			new Models\States\Devices\Repository($caching, $deviceLookup),
		);
	}

}
