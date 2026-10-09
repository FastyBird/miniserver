<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Consumers;

use ArrayObject;
use Error;
use FastyBird\Bridge\DevicesModuleUiModule;
use FastyBird\Bridge\DevicesModuleUiModule\Consumers as DevicesModuleUiModuleConsumers;
use FastyBird\Bridge\DevicesModuleUiModule\Exceptions as DevicesModuleUiModuleExceptions;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Entities\Topics as EntitiesTopics;
use FastyBird\Core\WebSockets\Routing;
use FastyBird\Core\WebSockets\Topics as WebSocketsTopics;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Consumers as DevicesConsumers;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Subscribers as DevicesSubscribers;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Consumers as UiConsumers;
use FastyBird\Module\Ui\Models as UiModels;
use Nette\DI;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_map;
use function is_array;
use function is_string;

/**
 * A WebSocket SET is a direct command for its target (a connector or device process), and is not
 * broadcast to the other WebSocket clients: the Devices source marks a command, the SocketsBridges
 * of both exchange topics skip a document that carries it, and the clients see the change when the
 * target reports it under its own source (#679).
 *
 * The three bridges are enabled as the ws-server enables them, behind the exchange consumer
 * container, the way a document that reaches this process from the exchange is consumed. The
 * bridge of this package is not registered in this package's container (census X4), so the test
 * registers the one it builds from the container's services, as SocketsBridgeTest does.
 *
 * What a connector reports about itself, its connection state, is written through the module's
 * helper and travels the whole way: state manager, state subscriber, publisher, consumers.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class SocketsBridgeCommandTest extends Tests\Cases\Unit\DbTestCase
{

	private const string DEVICES_TOPIC = '/devices-module/v1/exchange';

	private const string UI_TOPIC = '/ui-module/v1/exchange';

	// The fixtures' data source 32dd50e4-... reads this channel property
	private const string CHANNEL_PROPERTY = 'bbcccf8c-33ab-431b-a795-d7bb38b6b6db';

	private const string CHANNEL = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string DATA_SOURCE = '32dd50e4-4b66-4dea-9bc5-e835f8543dc4';

	// Added by device.state.property.sql: the connection state property of a device and the
	// data source that reads it
	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string DEVICE_STATE_PROPERTY = '5c1ae8a0-6f0b-4b7e-9c2d-3e4f5a6b7c8d';

	private const string DEVICE_STATE_DATA_SOURCE = 'd1a5c6e2-0b7f-4c3a-8e9d-1f2a3b4c5d6e';

	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/device.state.property.sql');
		$this->registerNeonConfigurationFile(__DIR__ . '/states.neon');

		parent::setUp();
	}

	/**
	 * What a SET produces, as the state document of the property it changed: the expected value it
	 * asks for, or an actual value it carries
	 *
	 * @return array<string, array{bool|float|int|string|null, bool|float|int|string|null}>
	 */
	public static function setStates(): array
	{
		return [
			'an expected value' => [null, 'on'],
			'an actual value' => ['on', null],
			'both' => ['on', 'on'],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('setStates')]
	public function testTheStateASetWritesIsBroadcastToNoWebSocketClient(
		bool|float|int|string|null $actualValue,
		bool|float|int|string|null $expectedValue,
	): void
	{
		$consumers = $this->enableTheBridges();

		$devices = $this->subscribe(self::DEVICES_TOPIC);
		$ui = $this->subscribe(self::UI_TOPIC);

		$consumers->consume(
			Sources\Module::DEVICES,
			Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
			$this->channelPropertyState($actualValue, $expectedValue),
		);

		self::assertSame([], $this->events($devices));
		self::assertSame([], $this->events($ui));
	}

	/**
	 * @throws Throwable
	 */
	public function testTheStateTheTargetReportsIsBroadcastToTheClientsOfBothTopicsOnce(): void
	{
		$consumers = $this->enableTheBridges();

		$devices = $this->subscribe(self::DEVICES_TOPIC);
		$ui = $this->subscribe(self::UI_TOPIC);

		$document = $this->channelPropertyState('on', null);

		$consumers->consume(
			Sources\Connector::VIRTUAL,
			Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
			$document,
		);

		self::assertSame(
			[
				[
					'topic' => self::DEVICES_TOPIC,
					'message' => [
						'routing_key' => Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
						'source' => Sources\Connector::VIRTUAL->value,
						'data' => $document->toArray(),
					],
				],
			],
			$this->events($devices),
		);

		$events = $this->events($ui);

		self::assertCount(1, $events);
		self::assertSame(self::UI_TOPIC, $events[0]['topic']);
		self::assertSame(
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$this->field($events[0]['message'], 'routing_key'),
		);
		self::assertSame(self::DATA_SOURCE, $this->field($this->field($events[0]['message'], 'data'), 'id'));
		self::assertSame('on', $this->field($this->field($events[0]['message'], 'data'), 'value'));
	}

	/**
	 * A connector's connection state is what the connector reports, so it is not a command: written
	 * through the module's helper with the connector's source, it is broadcast to the clients of
	 * the Devices topic, and to those of the Ui topic when a widget reads the property
	 *
	 * @throws Throwable
	 */
	public function testAConnectionStateTheConnectorReportsIsBroadcastToTheClientsOfBothTopicsOnce(): void
	{
		$consumers = $this->enableTheBridges();

		$devices = $this->subscribe(self::DEVICES_TOPIC);
		$ui = $this->subscribe(self::UI_TOPIC);

		$this->stateStorage($consumers);

		$device = $this->getContainer()->getByType(DevicesModels\Configuration\Devices\Repository::class)->find(
			Uuid\Uuid::fromString(self::DEVICE),
		);
		self::assertNotNull($device);

		$this->getContainer()->getByType(DevicesUtilities\DeviceConnection::class)->setState(
			$device,
			DevicesTypes\ConnectionState::CONNECTED,
			Sources\Connector::VIRTUAL,
		);

		$events = $this->events($devices);

		self::assertCount(1, $events);
		self::assertSame(self::DEVICES_TOPIC, $events[0]['topic']);
		self::assertSame(
			Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
			$this->field($events[0]['message'], 'routing_key'),
		);
		self::assertSame(Sources\Connector::VIRTUAL->value, $this->field($events[0]['message'], 'source'));
		self::assertSame(
			self::DEVICE_STATE_PROPERTY,
			$this->field($this->field($events[0]['message'], 'data'), 'id'),
		);

		$events = $this->events($ui);

		self::assertCount(1, $events);
		self::assertSame(self::UI_TOPIC, $events[0]['topic']);
		self::assertSame(
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$this->field($events[0]['message'], 'routing_key'),
		);
		self::assertSame(
			self::DEVICE_STATE_DATA_SOURCE,
			$this->field($this->field($events[0]['message'], 'data'), 'id'),
		);
		self::assertSame(
			DevicesTypes\ConnectionState::CONNECTED->value,
			$this->field($this->field($events[0]['message'], 'data'), 'value'),
		);
	}

	/**
	 * Enables the three bridges as the ws-server does when its server is created, and returns the
	 * container the exchange hands a document to
	 *
	 * @throws Throwable
	 */
	private function enableTheBridges(): ExchangeConsumers\Container
	{
		$container = $this->getContainer();

		$consumers = $container->getByType(ExchangeConsumers\Container::class);

		$consumers->register(
			new DevicesModuleUiModuleConsumers\SocketsBridge(
				$container->getByType(UiModels\Configuration\Widgets\DataSources\Repository::class),
				$this->service('fbDevicesModuleUiModuleBridge.logger', DevicesModuleUiModule\Logger::class),
				$container->getByType(Routing\LinkGenerator::class),
				$container->getByType(WebSocketsTopics\Storage::class),
			),
			null,
		);

		$consumers->enable(DevicesConsumers\SocketsBridge::class);
		$consumers->enable(UiConsumers\SocketsBridge::class);
		$consumers->enable(DevicesModuleUiModuleConsumers\SocketsBridge::class);

		return $consumers;
	}

	/**
	 * Stands for the state storage of a plugin, and for the exchange: what the module publishes
	 * reaches the consumers, as a document another ws-server publishes does
	 *
	 * @throws Throwable
	 */
	private function stateStorage(ExchangeConsumers\Container $consumers): void
	{
		$states = [];

		$storage = $this->createMock(DevicesModels\States\Devices\IManager::class);
		$storage->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use (&$states): DevicesStates\DeviceProperty {
				$actual = $values->offsetExists(DevicesStates\Property::ACTUAL_VALUE_FIELD)
					? $values->offsetGet(DevicesStates\Property::ACTUAL_VALUE_FIELD)
					: null;

				$states[$id->toString()] = new Tests\Fixtures\Dummy\DevicePropertyState(
					$id,
					is_string($actual) ? $actual : null,
					null,
					false,
					true,
				);

				return $states[$id->toString()];
			},
		);
		$storage->method('update')->willReturnCallback(
			static fn (Uuid\UuidInterface $id, Utils\ArrayHash $values): DevicesStates\DeviceProperty|false => $states[$id->toString()] ?? false,
		);

		$lookup = $this->createMock(DevicesModels\States\Devices\IRepository::class);
		$lookup->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): DevicesStates\DeviceProperty|null => $states[$id->toString()] ?? null,
		);

		$this->mockContainerService(
			DevicesModels\States\Devices\Manager::class,
			new DevicesModels\States\Devices\Manager($storage),
		);
		$this->mockContainerService(
			DevicesModels\States\Devices\Repository::class,
			new DevicesModels\States\Devices\Repository(
				$this->getContainer()->getByType(DevicesCaching\Container::class),
				$lookup,
			),
		);

		$exchange = $this->createMock(Publisher\MessagePublisher::class);
		$exchange->method('publish')->willReturnCallback(
			static function (Sources\Source $source, string $routingKey, CoreDocuments\Document|null $document) use ($consumers): bool {
				$consumers->consume($source, $routingKey, $document);

				return true;
			},
		);

		$this->getContainer()->getByType(Publisher\Container::class)->register($exchange);

		// The application's event dispatcher finds its subscribers by type; this container's does not
		$this->getContainer()->getByType(EventDispatcher\EventDispatcherInterface::class)->addSubscriber(
			$this->getContainer()->getByType(DevicesSubscribers\StateEntities::class),
		);
	}

	/**
	 * A service by name: the consumers and loggers are not autowired
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $type
	 *
	 * @return T
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesModuleUiModuleExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function service(string $name, string $type): object
	{
		$service = $this->getContainer()->getService($name);

		self::assertInstanceOf($type, $service);

		return $service;
	}

	/**
	 * Registers a topic with one subscribed client, and returns what that client is sent
	 *
	 * @return ArrayObject<int, string>
	 *
	 * @throws Throwable
	 */
	private function subscribe(string $topicId): ArrayObject
	{
		/** @var ArrayObject<int, string> $sent */
		$sent = new ArrayObject();

		$client = $this->createMock(Entities\Client::class);
		$client
			->method('send')
			->willReturnCallback(static function (mixed $response) use ($sent): void {
				$sent->append(is_string($response) ? $response : 'not a string');
			});

		$topic = new EntitiesTopics\Topic($topicId);
		$topic->add($client);

		$this->getContainer()->getByType(WebSocketsTopics\Storage::class)->addTopic($topicId, $topic);

		return $sent;
	}

	/**
	 * Decodes the WAMP EVENT frames a subscribed client was sent
	 *
	 * @param ArrayObject<int, string> $sent
	 *
	 * @return array<int, array{topic: mixed, message: mixed}>
	 *
	 * @throws Utils\JsonException
	 */
	private function events(ArrayObject $sent): array
	{
		return array_map(
			static function (string $frame): array {
				$event = Utils\Json::decode($frame, forceArrays: true);

				self::assertTrue(
					is_array($event) && ($event[0] ?? null) === Controllers\WampApplication::MSG_EVENT,
					'Not a WAMP EVENT frame: ' . $frame,
				);

				return [
					'topic' => $event[1] ?? null,
					'message' => is_string($event[2] ?? null)
						? Utils\Json::decode($event[2], forceArrays: true)
						: null,
				];
			},
			$sent->getArrayCopy(),
		);
	}

	private function field(mixed $message, string $key): mixed
	{
		self::assertIsArray($message);

		return $message[$key] ?? null;
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function channelPropertyState(
		bool|float|int|string|null $actualValue,
		bool|float|int|string|null $expectedValue,
	): DevicesDocuments\States\Channels\Properties\Property
	{
		return new DevicesDocuments\States\Channels\Properties\Property(
			Uuid\Uuid::fromString(self::CHANNEL_PROPERTY),
			Uuid\Uuid::fromString(self::CHANNEL),
			new DevicesDocuments\States\StateValues($actualValue, $expectedValue),
			new DevicesDocuments\States\StateValues(null, null),
			false,
			true,
		);
	}

}
