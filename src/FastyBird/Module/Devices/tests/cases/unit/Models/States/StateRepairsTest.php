<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\States;

use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Tests;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette\Caching as NetteCaching;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function is_array;
use function React\Async\await;

/**
 * The repair a property state manager stores when it reads a stored state that is no longer
 * valid for its property (#723).
 *
 * `readState()` of each of the six managers resets a stored actual value its property cannot
 * hold, or a stored expected value of a property that is not settable, and reads the state
 * again. What is recorded is exactly what reaches the state storage (`Writer::update()`): which
 * keys are present, their order and their values. The pins were written by hand and pass
 * unchanged on the code from before the repairs were handed on as plain arrays.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StateRepairsTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string CONNECTOR = '17c59dfa-2edd-438e-8c49-faa4e38e5a5e';

	private const string DEVICE = '69786d15-fd0c-4d9f-9378-33287c2009fa';

	private const string CHANNEL = '6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a';

	private const string PROPERTY = 'a3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d';

	/** @var list<array{string, string, string, array<mixed>}> */
	private array $stored = [];

	/**
	 * @return array<string, array{string, bool, array{string|null, string|null}, array<string, bool|null>}>
	 */
	public static function repairs(): array
	{
		$cases = [];

		foreach (['connector', 'device', 'channel'] as $owner) {
			foreach ([false, true] as $async) {
				$name = $owner . ($async ? ' async' : '') . ' manager';

				// The property is an enum of "on" and "off"
				$cases[$name . ', actual value the property cannot hold'] = [
					$owner,
					$async,
					['other', null],
					[
						States\Property::ACTUAL_VALUE_FIELD => null,
						States\Property::VALID_FIELD => false,
					],
				];

				// The property is not settable
				$cases[$name . ', expected value of a property that is not settable'] = [
					$owner,
					$async,
					['on', 'off'],
					[
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					],
				];
			}
		}

		return $cases;
	}

	/**
	 * @param array{string|null, string|null} $stored the actual and expected value of the stored state
	 * @param array<string, bool|null> $repair
	 *
	 * @throws Throwable
	 */
	#[DataProvider('repairs')]
	public function testAStoredStateThePropertyCannotHoldIsRepaired(
		string $owner,
		bool $async,
		array $stored,
		array $repair,
	): void
	{
		$this->recordStates($stored);

		$readState = [$this->manager($owner, $async), 'readState'];
		self::assertIsCallable($readState);

		$result = $readState($this->property($owner));

		if ($result instanceof Promise\PromiseInterface) {
			$result = await($result);
		}

		self::assertSame([[$owner, 'update', self::PROPERTY, $repair]], $this->stored);

		// Read again once repaired: the storage now holds a state with neither value
		self::assertNotNull($result);
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
	 * An enum of "on" and "off" that is not settable
	 *
	 * @throws Throwable
	 */
	private function property(
		string $owner,
	): DevicesDocuments\Connectors\Properties\Dynamic|DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic
	{
		$id = Uuid\Uuid::fromString(self::PROPERTY);

		if ($owner === 'connector') {
			return new DevicesDocuments\Connectors\Properties\Dynamic(
				$id,
				Uuid\Uuid::fromString(self::CONNECTOR),
				DevicesTypes\PropertyCategory::GENERIC,
				'property',
				null,
				ValuesTypes\DataType::ENUM,
				format: ['on', 'off'],
			);
		}

		if ($owner === 'device') {
			return new DevicesDocuments\Devices\Properties\Dynamic(
				$id,
				Uuid\Uuid::fromString(self::DEVICE),
				DevicesTypes\PropertyCategory::GENERIC,
				'property',
				null,
				ValuesTypes\DataType::ENUM,
				format: ['on', 'off'],
			);
		}

		return new DevicesDocuments\Channels\Properties\Dynamic(
			$id,
			Uuid\Uuid::fromString(self::CHANNEL),
			DevicesTypes\PropertyCategory::GENERIC,
			'property',
			null,
			ValuesTypes\DataType::ENUM,
			format: ['on', 'off'],
		);
	}

	/**
	 * Stands for the three state storages. Each holds the given state until a repair is stored,
	 * and a state with neither value after it. What is stored is recorded as a plain array. The
	 * asynchronous managers fall back to the synchronous storages, as they do with no
	 * asynchronous storage registered, and nothing is cached, so that the second read reaches
	 * the storage.
	 *
	 * @param array{string|null, string|null} $stored
	 *
	 * @throws Throwable
	 */
	private function recordStates(array $stored): void
	{
		$cache = new NetteCaching\Cache(new NetteCaching\Storages\DevNullStorage());
		$caching = new DevicesCaching\Container($cache, $cache, $cache, $cache);

		$repaired = false;

		$record = function (string $owner, Uuid\UuidInterface $id, mixed $values) use (&$repaired): void {
			self::assertTrue($values instanceof Utils\ArrayHash || is_array($values));

			$this->stored[] = [$owner, 'update', $id->toString(), (array) $values];

			$repaired = true;
		};

		$connectorWriter = $this->createMock(Models\States\Connectors\Writer::class);
		$connectorWriter->expects(self::never())->method('create');
		$connectorWriter->method('update')->willReturnCallback(
			static function (Uuid\UuidInterface $id, mixed $values) use ($record): States\ConnectorProperty {
				$record('connector', $id, $values);

				return new Tests\Fixtures\Dummy\ConnectorPropertyState($id, null, null, false, false);
			},
		);

		$connectorReader = $this->createMock(Models\States\Connectors\Reader::class);
		$connectorReader->method('find')->willReturnCallback(
			static function (Uuid\UuidInterface $id) use ($stored, &$repaired): States\ConnectorProperty {
				return $repaired
					? new Tests\Fixtures\Dummy\ConnectorPropertyState($id, null, null, false, false)
					: new Tests\Fixtures\Dummy\ConnectorPropertyState($id, $stored[0], $stored[1], false, true);
			},
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
		$deviceWriter->expects(self::never())->method('create');
		$deviceWriter->method('update')->willReturnCallback(
			static function (Uuid\UuidInterface $id, mixed $values) use ($record): States\DeviceProperty {
				$record('device', $id, $values);

				return new Tests\Fixtures\Dummy\DevicePropertyState($id, null, null, false, false);
			},
		);

		$deviceReader = $this->createMock(Models\States\Devices\Reader::class);
		$deviceReader->method('find')->willReturnCallback(
			static function (Uuid\UuidInterface $id) use ($stored, &$repaired): States\DeviceProperty {
				return $repaired
					? new Tests\Fixtures\Dummy\DevicePropertyState($id, null, null, false, false)
					: new Tests\Fixtures\Dummy\DevicePropertyState($id, $stored[0], $stored[1], false, true);
			},
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
		$channelWriter->expects(self::never())->method('create');
		$channelWriter->method('update')->willReturnCallback(
			static function (Uuid\UuidInterface $id, mixed $values) use ($record): States\ChannelProperty {
				$record('channel', $id, $values);

				return new Tests\Fixtures\Dummy\ChannelPropertyState($id, null, null, false, false);
			},
		);

		$channelReader = $this->createMock(Models\States\Channels\Reader::class);
		$channelReader->method('find')->willReturnCallback(
			static function (Uuid\UuidInterface $id) use ($stored, &$repaired): States\ChannelProperty {
				return $repaired
					? new Tests\Fixtures\Dummy\ChannelPropertyState($id, null, null, false, false)
					: new Tests\Fixtures\Dummy\ChannelPropertyState($id, $stored[0], $stored[1], false, true);
			},
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
	}

}
