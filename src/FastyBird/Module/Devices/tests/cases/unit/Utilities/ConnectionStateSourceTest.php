<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Utilities;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types;
use FastyBird\Module\Devices\Utilities;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;

/**
 * A connection state, and a property no longer being valid once a connector stops, are what the
 * connector reports about itself. They are published under the connector's source, never under
 * Devices: a document with the Devices source is a command (a WebSocket SET, an API write) and the
 * WebSocket clients do not receive it (#679).
 *
 * The utilities take the source of whoever reports the state. The connector lifecycle subscriber
 * reports for the connector itself, so it takes the source from the connector entity.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConnectionStateSourceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const array STATE_ROUTING_KEYS = [
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
	];

	/** @var list<array{source: int|string, routing_key: string}> */
	private array $published = [];

	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/connector.property.dynamic.sql');

		parent::setUp();
	}

	/**
	 * @throws Throwable
	 */
	public function testADeviceConnectionStateIsPublishedUnderTheSourceOfWhoReportsIt(): void
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

		self::assertSame(
			[
				[
					'source' => Sources\Connector::VIRTUAL->value,
					'routing_key' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testAConnectorConnectionStateIsPublishedUnderTheSourceOfWhoReportsIt(): void
	{
		$this->recordStates();

		$this->getContainer()->getByType(Utilities\ConnectorConnection::class)->setState(
			$this->connector(),
			Types\ConnectionState::RUNNING,
			Sources\Connector::VIRTUAL,
		);

		self::assertSame(
			[
				[
					'source' => Sources\Connector::VIRTUAL->value,
					'routing_key' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
				],
			],
			$this->published,
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testAConnectorThatStartsReportsItsStateUnderItsOwnSource(): void
	{
		$connector = $this->connector();

		$this->connectorEntityHasSource(Sources\Connector::VIRTUAL);
		$this->recordStates();

		$this->dispatch(new Events\AfterConnectorExecutionStart($connector));

		self::assertSame(
			[
				[
					'source' => Sources\Connector::VIRTUAL->value,
					'routing_key' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
				],
			],
			$this->published,
		);
	}

	/**
	 * Before it starts, a connector resets what it reports: its device connection states and the
	 * validity of every dynamic property of the connector, its devices and their channels
	 *
	 * @throws Throwable
	 */
	public function testAConnectorThatIsStartingResetsEverythingItReportsUnderItsOwnSource(): void
	{
		$connector = $this->connector();

		$this->connectorEntityHasSource(Sources\Connector::VIRTUAL);
		$this->recordStates();

		$this->dispatch(new Events\BeforeConnectorExecutionStart($connector));

		$sources = array_values(
			array_unique(array_map(static fn (array $item): int|string => $item['source'], $this->published)),
		);
		$kinds = [];

		foreach ($this->published as $item) {
			foreach ([
				'connector' => Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
				'device' => Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
				'channel' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
			] as $kind => $routingKey) {
				if ($item['routing_key'] === $routingKey) {
					$kinds[$kind] = true;
				}
			}
		}

		self::assertSame([Sources\Connector::VIRTUAL->value], $sources);
		self::assertSame(['connector', 'device', 'channel'], array_keys($kinds));
	}

	/**
	 * @throws Throwable
	 */
	private function connector(): Devices\Documents\Connectors\Connector
	{
		$connector = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class)->find(
			Uuid\Uuid::fromString(self::CONNECTOR),
		);
		self::assertNotNull($connector);

		return $connector;
	}

	/**
	 * The connector entity of the fixtures is a dummy that carries no connector source, as a
	 * connector of no known kind: it stands in for the entity of a real connector
	 *
	 * @throws Throwable
	 */
	private function connectorEntityHasSource(Sources\Source $source): void
	{
		$entity = $this->createMock(Entities\Connectors\Connector::class);
		$entity->method('getSource')->willReturn($source);

		// Only the lookup of the connector that starts is replaced: the configuration is built
		// from this repository too
		$real = $this->getContainer()->getByType(Models\Entities\Connectors\ConnectorsRepository::class);

		$entityRepository = $this->createMock(ORM\EntityRepository::class);
		$entityRepository->method('find')->willReturn($entity);
		$entityRepository->method('findAll')->willReturnCallback(static fn (): array => $real->findAll());

		$registry = $this->createMock(Persistence\ManagerRegistry::class);
		$registry->method('getRepository')->willReturn($entityRepository);

		$this->mockContainerService(
			Models\Entities\Connectors\ConnectorsRepository::class,
			new Models\Entities\Connectors\ConnectorsRepository(
				$this->getContainer()->getByType(Helpers\Database::class),
				$registry,
			),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function dispatch(object $event): void
	{
		$dispatcher = $this->getContainer()->getByType(EventDispatcher\EventDispatcherInterface::class);
		$dispatcher->addSubscriber($this->getContainer()->getByType(Subscribers\Connector::class));
		$dispatcher->dispatch($event);
	}

	/**
	 * Stands for a state storage that accepts what is written to it, and records what the module
	 * then publishes to the exchange as a property state: the source and the routing key
	 *
	 * @throws Throwable
	 */
	private function recordStates(): void
	{
		$caching = $this->getContainer()->getByType(DevicesCaching\Container::class);

		$connectorStates = [];

		$connectorStorage = $this->createMock(Models\States\Connectors\IManager::class);
		$connectorStorage->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use (&$connectorStates): States\ConnectorProperty {
				$connectorStates[$id->toString()] = new Tests\Fixtures\Dummy\ConnectorPropertyState(
					$id,
					null,
					null,
					false,
					true,
				);

				return $connectorStates[$id->toString()];
			},
		);
		$connectorStorage->method('update')->willReturnCallback(
			static fn (Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ConnectorProperty|false => $connectorStates[$id->toString()] ?? false,
		);

		$connectorLookup = $this->createMock(Models\States\Connectors\IRepository::class);
		$connectorLookup->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): States\ConnectorProperty|null => $connectorStates[$id->toString()] ?? null,
		);

		$this->mockContainerService(
			Models\States\Connectors\Manager::class,
			new Models\States\Connectors\Manager($connectorStorage),
		);
		$this->mockContainerService(
			Models\States\Connectors\Repository::class,
			new Models\States\Connectors\Repository($caching, $connectorLookup),
		);

		$deviceStates = [];

		$deviceStorage = $this->createMock(Models\States\Devices\IManager::class);
		$deviceStorage->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use (&$deviceStates): States\DeviceProperty {
				$deviceStates[$id->toString()] = new Tests\Fixtures\Dummy\DevicePropertyState(
					$id,
					null,
					null,
					false,
					true,
				);

				return $deviceStates[$id->toString()];
			},
		);
		$deviceStorage->method('update')->willReturnCallback(
			static fn (Uuid\UuidInterface $id, Utils\ArrayHash $values): States\DeviceProperty|false => $deviceStates[$id->toString()] ?? false,
		);

		$deviceLookup = $this->createMock(Models\States\Devices\IRepository::class);
		$deviceLookup->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): States\DeviceProperty|null => $deviceStates[$id->toString()] ?? null,
		);

		$this->mockContainerService(
			Models\States\Devices\Manager::class,
			new Models\States\Devices\Manager($deviceStorage),
		);
		$this->mockContainerService(
			Models\States\Devices\Repository::class,
			new Models\States\Devices\Repository($caching, $deviceLookup),
		);

		$channelStates = [];

		$channelStorage = $this->createMock(Models\States\Channels\IManager::class);
		$channelStorage->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use (&$channelStates): States\ChannelProperty {
				$channelStates[$id->toString()] = new Tests\Fixtures\Dummy\ChannelPropertyState(
					$id,
					null,
					null,
					false,
					true,
				);

				return $channelStates[$id->toString()];
			},
		);
		$channelStorage->method('update')->willReturnCallback(
			static fn (Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ChannelProperty|false => $channelStates[$id->toString()] ?? false,
		);

		$channelLookup = $this->createMock(Models\States\Channels\IRepository::class);
		$channelLookup->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): States\ChannelProperty|null => $channelStates[$id->toString()] ?? null,
		);

		$this->mockContainerService(
			Models\States\Channels\Manager::class,
			new Models\States\Channels\Manager($channelStorage),
		);
		$this->mockContainerService(
			Models\States\Channels\Repository::class,
			new Models\States\Channels\Repository($caching, $channelLookup),
		);

		$recorder = $this->createMock(Publisher\MessagePublisher::class);
		$recorder->method('publish')->willReturnCallback(
			function (Sources\Source $source, string $routingKey, CoreDocuments\Document|null $document): bool {
				if (in_array($routingKey, self::STATE_ROUTING_KEYS, true)) {
					$this->published[] = ['source' => $source->value, 'routing_key' => $routingKey];
				}

				return true;
			},
		);

		$this->getContainer()->getByType(Publisher\Container::class)->register($recorder);

		// The application's event dispatcher finds its subscribers by type; this container's does not
		$this->getContainer()->getByType(EventDispatcher\EventDispatcherInterface::class)->addSubscriber(
			$this->getContainer()->getByType(Subscribers\StateEntities::class),
		);
	}

}
