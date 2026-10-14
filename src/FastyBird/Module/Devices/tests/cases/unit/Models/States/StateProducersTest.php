<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\States;

use DateTimeImmutable;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Consumers;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function count;
use function is_string;
use function React\Async\await;

/**
 * The state the Devices module's property state managers hand to a state storage (#723).
 *
 * What is recorded is exactly what reaches the storage (`Writer::create()`/`update()`): which
 * keys are present, their order and their values. A key that is not given must stay absent, a
 * key given as null must arrive as null.
 *
 * The producer tests pin, by hand, what the module's own producers store: the valid and pending
 * helpers of the six managers, and the exchange consumer that carries a property action on to
 * the asynchronous managers. They pass unchanged on the code from before PropertyUpdate. The
 * PropertyUpdate tests show that a manager stores and publishes a PropertyUpdate exactly as the
 * ArrayHash its callers built for the same change.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StateProducersTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string CHANNEL = '6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a';

	// Inserted by recordStates(): the fixtures hold no settable string property
	private const string PROPERTY = 'a3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d';

	private const string OTHER_PROPERTY = 'b4e2d3c5-6f7a-4b8c-9d0e-1f2a3b4c5d6e';

	// The test container's frozen clock, as the pending helpers format it
	private const string NOW = '2020-04-01T12:00:00+00:00';

	/** @var list<array{string, string, string, array<mixed>}> */
	private array $stored = [];

	/** @var list<array{int|string, string, class-string|null, array<string, mixed>|null}> */
	private array $published = [];

	/**
	 * @return array<string, array{string, bool}>
	 */
	public static function managers(): array
	{
		$cases = [];

		foreach (['connector', 'device', 'channel'] as $owner) {
			$cases[$owner . ' manager'] = [$owner, false];
			$cases[$owner . ' async manager'] = [$owner, true];
		}

		return $cases;
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('managers')]
	public function testValidStateHelperStoresOnlyTheValidFlag(string $owner, bool $async): void
	{
		$this->recordStates(null);

		$this->call(
			$owner,
			$async,
			'setValidState',
			$this->property($owner, self::PROPERTY),
			true,
			Sources\Module::DEVICES,
		);
		$this->call(
			$owner,
			$async,
			'setValidState',
			[$this->property($owner, self::PROPERTY), $this->property($owner, self::OTHER_PROPERTY)],
			false,
			Sources\Module::DEVICES,
		);

		self::assertSame(
			[
				[$owner, 'create', self::PROPERTY, [States\Property::VALID_FIELD => true]],
				[$owner, 'create', self::PROPERTY, [States\Property::VALID_FIELD => false]],
				[$owner, 'create', self::OTHER_PROPERTY, [States\Property::VALID_FIELD => false]],
			],
			$this->stored,
		);
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('managers')]
	public function testPendingStateHelperStoresTheTimestampOrClearsTheExpectedValue(string $owner, bool $async): void
	{
		$this->recordStates(null);

		$this->call(
			$owner,
			$async,
			'setPendingState',
			$this->property($owner, self::PROPERTY),
			true,
			Sources\Module::DEVICES,
		);
		$this->call(
			$owner,
			$async,
			'setPendingState',
			[$this->property($owner, self::PROPERTY), $this->property($owner, self::OTHER_PROPERTY)],
			true,
			Sources\Module::DEVICES,
		);
		$this->call(
			$owner,
			$async,
			'setPendingState',
			$this->property($owner, self::PROPERTY),
			false,
			Sources\Module::DEVICES,
		);
		$this->call(
			$owner,
			$async,
			'setPendingState',
			[$this->property($owner, self::OTHER_PROPERTY)],
			false,
			Sources\Module::DEVICES,
		);

		self::assertSame(
			[
				[$owner, 'create', self::PROPERTY, [States\Property::PENDING_FIELD => self::NOW]],
				[$owner, 'create', self::PROPERTY, [States\Property::PENDING_FIELD => self::NOW]],
				[$owner, 'create', self::OTHER_PROPERTY, [States\Property::PENDING_FIELD => self::NOW]],
				[
					$owner,
					'create',
					self::PROPERTY,
					[States\Property::EXPECTED_VALUE_FIELD => null, States\Property::PENDING_FIELD => false],
				],
				[
					$owner,
					'create',
					self::OTHER_PROPERTY,
					[States\Property::EXPECTED_VALUE_FIELD => null, States\Property::PENDING_FIELD => false],
				],
			],
			$this->stored,
		);
	}

	/**
	 * A stored state takes the update path: clearing the pending flag clears the expected value
	 * the storage holds, and leaves the actual value and the valid flag alone
	 *
	 * @throws Throwable
	 */
	#[DataProvider('managers')]
	public function testPendingStateHelperUpdatesAStoredState(string $owner, bool $async): void
	{
		$this->recordStates(['actual', 'expected']);

		$this->call(
			$owner,
			$async,
			'setPendingState',
			$this->property($owner, self::PROPERTY),
			false,
			Sources\Module::DEVICES,
		);
		$this->call(
			$owner,
			$async,
			'setValidState',
			$this->property($owner, self::PROPERTY),
			false,
			Sources\Module::DEVICES,
		);

		self::assertSame(
			[
				[
					$owner,
					'update',
					self::PROPERTY,
					[States\Property::EXPECTED_VALUE_FIELD => null, States\Property::PENDING_FIELD => false],
				],
				[$owner, 'update', self::PROPERTY, [States\Property::VALID_FIELD => false]],
			],
			$this->stored,
		);
	}

	/**
	 * @return array<string, array{string, string, array<string, mixed>, array<string, mixed>}>
	 */
	public static function actions(): array
	{
		$cases = [];

		foreach (['connector', 'device', 'channel'] as $owner) {
			$cases[$owner . ' set of both values'] = [
				$owner,
				'set',
				[States\Property::ACTUAL_VALUE_FIELD => 'on', States\Property::EXPECTED_VALUE_FIELD => 'off'],
				[
					States\Property::ACTUAL_VALUE_FIELD => 'on',
					States\Property::EXPECTED_VALUE_FIELD => 'off',
					States\Property::VALID_FIELD => true,
					States\Property::PENDING_FIELD => true,
				],
			];

			$cases[$owner . ' set of the actual value alone'] = [
				$owner,
				'set',
				[States\Property::ACTUAL_VALUE_FIELD => 'on'],
				[
					States\Property::ACTUAL_VALUE_FIELD => 'on',
					States\Property::VALID_FIELD => true,
				],
			];

			$cases[$owner . ' set of a null expected value'] = [
				$owner,
				'set',
				[States\Property::EXPECTED_VALUE_FIELD => null],
				[
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				],
			];

			$cases[$owner . ' write of the expected value'] = [
				$owner,
				'write',
				[States\Property::EXPECTED_VALUE_FIELD => 'off'],
				[
					States\Property::EXPECTED_VALUE_FIELD => 'off',
					States\Property::PENDING_FIELD => true,
				],
			];
		}

		return $cases;
	}

	/**
	 * The exchange consumer carries a property action on to the asynchronous manager with the
	 * values the action names, and only those
	 *
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $expected
	 *
	 * @throws Throwable
	 */
	#[DataProvider('actions')]
	public function testStatesActionsConsumerStoresTheActionValues(
		string $owner,
		string $action,
		array $values,
		array $expected,
	): void
	{
		$this->recordStates(null);

		[$routingKey, $document] = $this->action($owner, $action, $values);

		// The consumer is collected by the exchange, not autowired
		$consumers = $this->getContainer()->findByType(Consumers\StatesActions::class);
		self::assertCount(1, $consumers);

		$consumer = $this->getContainer()->getService($consumers[0]);
		self::assertInstanceOf(Consumers\StatesActions::class, $consumer);

		$consumer->consume(Sources\Module::DEVICES, $routingKey, $document);

		self::assertSame([[$owner, 'create', self::PROPERTY, $expected]], $this->stored);
	}

	/**
	 * @return array<string, array{string, bool, string, bool}>
	 */
	public static function changes(): array
	{
		$cases = [];

		foreach (self::managers() as $name => [$owner, $async]) {
			foreach (['set', 'write'] as $method) {
				$cases[$name . ' ' . $method] = [$owner, $async, $method, false];
				$cases[$name . ' ' . $method . ' over a stored state'] = [$owner, $async, $method, true];
			}
		}

		return $cases;
	}

	/**
	 * A manager stores a PropertyUpdate exactly as it stores the ArrayHash its callers built
	 * for the same change before PropertyUpdate existed
	 *
	 * @throws Throwable
	 */
	#[DataProvider('changes')]
	public function testAChangeIsStoredAsTheArrayHashItStandsFor(
		string $owner,
		bool $async,
		string $method,
		bool $stored,
	): void
	{
		$this->recordStates($stored ? ['actual', 'expected'] : null);

		$property = $this->property($owner, self::PROPERTY);

		$changes = [
			[States\Property::EXPECTED_VALUE_FIELD => 'off'],
			[States\Property::EXPECTED_VALUE_FIELD => 'N/A'],
			[States\Property::EXPECTED_VALUE_FIELD => null, States\Property::PENDING_FIELD => false],
			[States\Property::PENDING_FIELD => self::NOW],
			[States\Property::VALID_FIELD => false],
		];

		// Only a set may carry the actual value: an asynchronous write refuses it
		if ($method === 'set') {
			$changes[] = [States\Property::ACTUAL_VALUE_FIELD => 'on', States\Property::EXPECTED_VALUE_FIELD => null];
			$changes[] = [States\Property::ACTUAL_VALUE_FIELD => 'N/A'];
			$changes[] = [States\Property::VALID_FIELD => true, States\Property::ACTUAL_VALUE_FIELD => 'expected'];
		}

		foreach ($changes as $change) {
			$this->call($owner, $async, $method, $property, Utils\ArrayHash::from($change), Sources\Module::DEVICES);
		}

		$fromArrayHashes = $this->stored;
		$this->stored = [];

		foreach ($changes as $change) {
			$this->call($owner, $async, $method, $property, self::update($change), Sources\Module::DEVICES);
		}

		self::assertCount(count($changes), $fromArrayHashes);
		self::assertSame($fromArrayHashes, $this->stored);
	}

	/**
	 * The string 'N/A', `Utilities\Value::NOT_SET`, is stored as the value it is
	 *
	 * @throws Throwable
	 */
	#[DataProvider('managers')]
	public function testTheNotSetStringIsStoredAsAValue(string $owner, bool $async): void
	{
		$this->recordStates(null);

		$this->call(
			$owner,
			$async,
			'set',
			$this->property($owner, self::PROPERTY),
			States\PropertyUpdate::create()->withActualValue('N/A'),
			Sources\Module::DEVICES,
		);

		self::assertSame(
			[
				[
					$owner,
					'create',
					self::PROPERTY,
					[States\Property::ACTUAL_VALUE_FIELD => 'N/A', States\Property::VALID_FIELD => true],
				],
			],
			$this->stored,
		);
	}

	/**
	 * Over a stored state, a field given as null clears it and a field not given is left alone
	 *
	 * @throws Throwable
	 */
	#[DataProvider('managers')]
	public function testNullClearsAFieldAndAnAbsentFieldIsLeftAlone(string $owner, bool $async): void
	{
		$this->recordStates(['actual', 'expected']);

		$property = $this->property($owner, self::PROPERTY);

		$this->call(
			$owner,
			$async,
			'set',
			$property,
			States\PropertyUpdate::create()->withExpectedValue(null),
			Sources\Module::DEVICES,
		);
		$this->call(
			$owner,
			$async,
			'set',
			$property,
			States\PropertyUpdate::create()->withActualValue('on'),
			Sources\Module::DEVICES,
		);

		self::assertSame(
			[
				[
					$owner,
					'update',
					self::PROPERTY,
					[States\Property::EXPECTED_VALUE_FIELD => null, States\Property::PENDING_FIELD => false],
				],
				[
					$owner,
					'update',
					self::PROPERTY,
					[States\Property::ACTUAL_VALUE_FIELD => 'on', States\Property::VALID_FIELD => true],
				],
			],
			$this->stored,
		);
	}

	/**
	 * With the exchange on, a manager publishes a PropertyUpdate exactly as it publishes the
	 * ArrayHash its callers built for the same change
	 *
	 * @throws Throwable
	 */
	#[DataProvider('managers')]
	public function testAChangeIsPublishedAsTheArrayHashItStandsFor(string $owner, bool $async): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/../../Controllers/exchange.neon');

		$publisher = $this->createMock(Publisher\MessagePublisher::class);
		$publisher->method('publish')->willReturnCallback(
			function (
				Sources\Source $source,
				string $routingKey,
				CoreDocuments\Document|null $document,
			): bool {
				$this->published[] = [
					$source->value,
					$routingKey,
					$document !== null ? $document::class : null,
					$document?->toArray(),
				];

				return true;
			},
		);
		$this->getContainer()->getByType(Publisher\Container::class)->register($publisher);

		$asyncPublisher = $this->createMock(Publisher\Async\MessagePublisher::class);
		$asyncPublisher->method('publish')->willReturnCallback(
			function (
				Sources\Source $source,
				string $routingKey,
				CoreDocuments\Document|null $document,
			): Promise\PromiseInterface {
				$this->published[] = [
					$source->value,
					$routingKey,
					$document !== null ? $document::class : null,
					$document?->toArray(),
				];

				return Promise\resolve(true);
			},
		);
		$this->getContainer()->getByType(Publisher\Async\Container::class)->register($asyncPublisher);

		$property = $this->property($owner, self::PROPERTY);

		$changes = [
			[States\Property::ACTUAL_VALUE_FIELD => 'on', States\Property::EXPECTED_VALUE_FIELD => null],
			[States\Property::EXPECTED_VALUE_FIELD => 'off'],
			[States\Property::ACTUAL_VALUE_FIELD => 'on'],
		];

		foreach (['set', 'write'] as $method) {
			foreach ($changes as $change) {
				$this->call(
					$owner,
					$async,
					$method,
					$property,
					Utils\ArrayHash::from($change),
					Sources\Module::DEVICES,
				);
			}
		}

		$fromArrayHashes = $this->published;
		$this->published = [];

		foreach (['set', 'write'] as $method) {
			foreach ($changes as $change) {
				$this->call($owner, $async, $method, $property, self::update($change), Sources\Module::DEVICES);
			}
		}

		self::assertCount(6, $fromArrayHashes);
		self::assertSame($fromArrayHashes, $this->published);
	}

	/**
	 * The PropertyUpdate for a change given as the array its callers built, field by field in
	 * the array's order; the pending time is the ATOM string the callers formatted
	 *
	 * @param array<string, bool|float|int|string|null> $change
	 *
	 * @throws Throwable
	 */
	private static function update(array $change): States\PropertyUpdate
	{
		$update = States\PropertyUpdate::create();

		foreach ($change as $field => $value) {
			$update = match ($field) {
				States\Property::ACTUAL_VALUE_FIELD => $update->withActualValue($value),
				States\Property::EXPECTED_VALUE_FIELD => $update->withExpectedValue($value),
				States\Property::PENDING_FIELD => $update->withPending(
					is_string($value) ? new DateTimeImmutable($value) : $value === true,
				),
				default => $update->withValid($value === true),
			};
		}

		return $update;
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @return array{string, CoreDocuments\Document}
	 *
	 * @throws Throwable
	 */
	private function action(string $owner, string $action, array $values): array
	{
		$documentFactory = $this->getContainer()->getByType(CoreDocuments\DocumentFactory::class);

		$data = [
			'action' => DevicesTypes\PropertyAction::SET->value,
			'property' => self::PROPERTY,
			$action => $values,
		];

		if ($owner === 'connector') {
			return [
				DevicesTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION->value,
				$documentFactory->create(
					DevicesDocuments\States\Connectors\Properties\Actions\Action::class,
					$data + ['connector' => self::CONNECTOR],
				),
			];
		}

		if ($owner === 'device') {
			return [
				DevicesTypes\RoutingKey::DEVICE_PROPERTY_ACTION->value,
				$documentFactory->create(
					DevicesDocuments\States\Devices\Properties\Actions\Action::class,
					$data + ['device' => self::DEVICE],
				),
			];
		}

		return [
			DevicesTypes\RoutingKey::CHANNEL_PROPERTY_ACTION->value,
			$documentFactory->create(
				DevicesDocuments\States\Channels\Properties\Actions\Action::class,
				$data + ['channel' => self::CHANNEL],
			),
		];
	}

	/**
	 * @throws Throwable
	 */
	private function manager(string $owner, bool $async): object
	{
		$container = $this->getContainer();

		return match ([$owner, $async]) {
			['connector', false] => $container->getByType(Models\States\ConnectorPropertiesManager::class),
			['device', false] => $container->getByType(Models\States\DevicePropertiesManager::class),
			['channel', false] => $container->getByType(Models\States\ChannelPropertiesManager::class),
			['connector', true] => $container->getByType(Models\States\Async\ConnectorPropertiesManager::class),
			['device', true] => $container->getByType(Models\States\Async\DevicePropertiesManager::class),
			default => $container->getByType(Models\States\Async\ChannelPropertiesManager::class),
		};
	}

	/**
	 * @throws Throwable
	 */
	private function property(
		string $owner,
		string $id,
	): DevicesDocuments\Connectors\Properties\Dynamic|DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic
	{
		if ($owner === 'connector') {
			return new DevicesDocuments\Connectors\Properties\Dynamic(
				Uuid\Uuid::fromString($id),
				Uuid\Uuid::fromString(self::CONNECTOR),
				DevicesTypes\PropertyCategory::GENERIC,
				$id === self::PROPERTY ? 'property' : 'other-property',
				null,
				ValuesTypes\DataType::STRING,
				settable: true,
			);
		}

		if ($owner === 'device') {
			return new DevicesDocuments\Devices\Properties\Dynamic(
				Uuid\Uuid::fromString($id),
				Uuid\Uuid::fromString(self::DEVICE),
				DevicesTypes\PropertyCategory::GENERIC,
				$id === self::PROPERTY ? 'property' : 'other-property',
				null,
				ValuesTypes\DataType::STRING,
				settable: true,
			);
		}

		return new DevicesDocuments\Channels\Properties\Dynamic(
			Uuid\Uuid::fromString($id),
			Uuid\Uuid::fromString(self::CHANNEL),
			DevicesTypes\PropertyCategory::GENERIC,
			$id === self::PROPERTY ? 'property' : 'other-property',
			null,
			ValuesTypes\DataType::STRING,
			settable: true,
		);
	}

	/**
	 * Calls a method the six managers share on the manager of the owner, and runs a promise an
	 * asynchronous one returns to its end
	 *
	 * @throws Throwable
	 */
	private function call(string $owner, bool $async, string $method, mixed ...$arguments): void
	{
		$callable = [$this->manager($owner, $async), $method];
		self::assertIsCallable($callable);

		$result = $callable(...$arguments);

		if ($result instanceof Promise\PromiseInterface) {
			await($result);
		}
	}

	/**
	 * Stands for the three state storages, recording what is stored in each as a plain array.
	 * The asynchronous managers fall back to the synchronous storages, as they do with no
	 * asynchronous storage registered. The properties are stored as well, with no mapped child,
	 * so that the exchange consumer finds the property the action names.
	 *
	 * @param array{string, string}|null $stored the actual and expected value of a stored state, or null for none
	 *
	 * @throws Throwable
	 */
	private function recordStates(array|null $stored): void
	{
		$caching = $this->getContainer()->getByType(DevicesCaching\Container::class);

		$record = function (string $owner, string $operation, Uuid\UuidInterface $id, Utils\ArrayHash $values): void {
			$this->stored[] = [$owner, $operation, $id->toString(), (array) $values];
		};

		$connectorWriter = $this->createMock(Models\States\Connectors\Writer::class);
		$connectorWriter->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use ($record): States\ConnectorProperty {
				$record('connector', 'create', $id, $values);

				return new Tests\Fixtures\Dummy\ConnectorPropertyState($id, null, null, false, true);
			},
		);
		$connectorWriter->method('update')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use ($record): States\ConnectorProperty {
				$record('connector', 'update', $id, $values);

				return new Tests\Fixtures\Dummy\ConnectorPropertyState($id, 'updated', null, false, false);
			},
		);

		$connectorReader = $this->createMock(Models\States\Connectors\Reader::class);
		$connectorReader->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): States\ConnectorProperty|null => $stored !== null
				? new Tests\Fixtures\Dummy\ConnectorPropertyState($id, $stored[0], $stored[1], true, true)
				: null,
		);

		$connectorManager = new Models\States\Connectors\Manager($connectorWriter);
		$connectorRepository = new Models\States\Connectors\Repository($caching, $connectorReader);

		$this->mockContainerService(Models\States\Connectors\Manager::class, $connectorManager);
		$this->mockContainerService(Models\States\Connectors\Repository::class, $connectorRepository);
		$this->mockContainerService(
			Models\States\Connectors\Async\Manager::class,
			new Models\States\Connectors\Async\Manager($connectorManager),
		);
		$this->mockContainerService(
			Models\States\Connectors\Async\Repository::class,
			new Models\States\Connectors\Async\Repository($connectorRepository, $caching),
		);

		$deviceWriter = $this->createMock(Models\States\Devices\Writer::class);
		$deviceWriter->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use ($record): States\DeviceProperty {
				$record('device', 'create', $id, $values);

				return new Tests\Fixtures\Dummy\DevicePropertyState($id, null, null, false, true);
			},
		);
		$deviceWriter->method('update')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use ($record): States\DeviceProperty {
				$record('device', 'update', $id, $values);

				return new Tests\Fixtures\Dummy\DevicePropertyState($id, 'updated', null, false, false);
			},
		);

		$deviceReader = $this->createMock(Models\States\Devices\Reader::class);
		$deviceReader->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): States\DeviceProperty|null => $stored !== null
				? new Tests\Fixtures\Dummy\DevicePropertyState($id, $stored[0], $stored[1], true, true)
				: null,
		);

		$deviceManager = new Models\States\Devices\Manager($deviceWriter);
		$deviceRepository = new Models\States\Devices\Repository($caching, $deviceReader);

		$this->mockContainerService(Models\States\Devices\Manager::class, $deviceManager);
		$this->mockContainerService(Models\States\Devices\Repository::class, $deviceRepository);
		$this->mockContainerService(
			Models\States\Devices\Async\Manager::class,
			new Models\States\Devices\Async\Manager($deviceManager),
		);
		$this->mockContainerService(
			Models\States\Devices\Async\Repository::class,
			new Models\States\Devices\Async\Repository($deviceRepository, $caching),
		);

		$channelWriter = $this->createMock(Models\States\Channels\Writer::class);
		$channelWriter->method('create')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use ($record): States\ChannelProperty {
				$record('channel', 'create', $id, $values);

				return new Tests\Fixtures\Dummy\ChannelPropertyState($id, null, null, false, true);
			},
		);
		$channelWriter->method('update')->willReturnCallback(
			static function (Uuid\UuidInterface $id, Utils\ArrayHash $values) use ($record): States\ChannelProperty {
				$record('channel', 'update', $id, $values);

				return new Tests\Fixtures\Dummy\ChannelPropertyState($id, 'updated', null, false, false);
			},
		);

		$channelReader = $this->createMock(Models\States\Channels\Reader::class);
		$channelReader->method('find')->willReturnCallback(
			static fn (Uuid\UuidInterface $id): States\ChannelProperty|null => $stored !== null
				? new Tests\Fixtures\Dummy\ChannelPropertyState($id, $stored[0], $stored[1], true, true)
				: null,
		);

		$channelManager = new Models\States\Channels\Manager($channelWriter);
		$channelRepository = new Models\States\Channels\Repository($caching, $channelReader);

		$this->mockContainerService(Models\States\Channels\Manager::class, $channelManager);
		$this->mockContainerService(Models\States\Channels\Repository::class, $channelRepository);
		$this->mockContainerService(
			Models\States\Channels\Async\Manager::class,
			new Models\States\Channels\Async\Manager($channelManager),
		);
		$this->mockContainerService(
			Models\States\Channels\Async\Repository::class,
			new Models\States\Channels\Async\Repository($channelRepository, $caching),
		);

		// The properties the exchange consumer looks up, with no mapped child
		foreach (
			[
				'connector' => ['connectors', 'connector_id', self::CONNECTOR],
				'device' => ['devices', 'device_id', self::DEVICE],
				'channel' => ['channels', 'channel_id', self::CHANNEL],
			] as [$table, $column, $ownerId]
		) {
			foreach ([self::PROPERTY => 'property', self::OTHER_PROPERTY => 'other-property'] as $id => $identifier) {
				$this->getDb()->executeStatement(
					'INSERT INTO `fb_devices_module_' . $table . '_properties` (`property_id`, `' . $column . '`,'
					. ' `property_type`, `property_identifier`, `property_name`, `property_settable`,'
					. ' `property_queryable`, `property_data_type`, `created_at`, `updated_at`)'
					. ' VALUES (UNHEX(?), UNHEX(?), ?, ?, NULL, 1, 0, ?, ?, ?)',
					[
						Utils\Strings::replace($id, '/-/', ''),
						Utils\Strings::replace($ownerId, '/-/', ''),
						'dynamic',
						$identifier,
						'string',
						'2026-10-14 12:00:00',
						'2026-10-14 12:00:00',
					],
				);
			}
		}
	}

}
