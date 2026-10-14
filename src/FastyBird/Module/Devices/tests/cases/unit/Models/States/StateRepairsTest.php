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
 *
 * A stored expected value the property's equation transformer cannot evaluate (#782) is repaired
 * the same way as one the property cannot hold: the managers store it as nulled and no longer
 * pending, then read the state again.
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
	 * Three ways an equation transformer refuses to evaluate a value, for each of the six managers.
	 * The y-equation (over `x`) is the one applied to a stored value read for display.
	 *
	 * @return array<string, array{string, bool, ValuesTypes\DataType, string, string}>
	 */
	public static function unevaluableExpectedValues(): array
	{
		$equations = [
			'division by zero' => [ValuesTypes\DataType::FLOAT, 'equation:x=y|y=x/0', '10'],
			'result that is not a finite number' => [ValuesTypes\DataType::FLOAT, 'equation:x=y|y=(-x)^(1/2)', '4'],
			'integer result out of the int range' => [ValuesTypes\DataType::INT, 'equation:x=y|y=x*10^19', '1'],
		];

		$cases = [];

		foreach (['connector', 'device', 'channel'] as $owner) {
			foreach ([false, true] as $async) {
				foreach ($equations as $trigger => [$dataType, $transformer, $expected]) {
					$cases[$owner . ($async ? ' async' : '') . ' manager, ' . $trigger] = [
						$owner,
						$async,
						$dataType,
						$transformer,
						$expected,
					];
				}
			}
		}

		return $cases;
	}

	/**
	 * The repair is the one stored for an expected value of a property that is not settable: the
	 * value is nulled and the state is no longer pending, since nothing is on its way to the device
	 * any more. It is the expected-value twin of the actual value's repair (nulled, not valid).
	 *
	 * @throws Throwable
	 */
	#[DataProvider('unevaluableExpectedValues')]
	public function testAStoredExpectedValueItsEquationCannotEvaluateIsRepaired(
		string $owner,
		bool $async,
		ValuesTypes\DataType $dataType,
		string $transformer,
		string $expected,
	): void
	{
		$this->recordStates([null, $expected]);

		$readState = [$this->manager($owner, $async), 'readState'];
		self::assertIsCallable($readState);

		$result = $readState($this->property($owner, $dataType, null, $transformer, true));

		if ($result instanceof Promise\PromiseInterface) {
			$result = await($result);
		}

		self::assertSame(
			[
				[
					$owner,
					'update',
					self::PROPERTY,
					[
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					],
				],
			],
			$this->stored,
		);

		self::assertInstanceOf(DevicesDocuments\States\Property::class, $result);
		self::assertNull($result->getRead()->getExpectedValue());
		self::assertNull($result->getGet()->getExpectedValue());
		self::assertFalse($result->isPending());
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
	 * An enum of "on" and "off" that is not settable, unless told otherwise
	 *
	 * @param list<string>|null $format
	 *
	 * @throws Throwable
	 */
	private function property(
		string $owner,
		ValuesTypes\DataType $dataType = ValuesTypes\DataType::ENUM,
		array|null $format = ['on', 'off'],
		string|null $transformer = null,
		bool $settable = false,
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
				$dataType,
				format: $format,
				valueTransformer: $transformer,
				settable: $settable,
			);
		}

		if ($owner === 'device') {
			return new DevicesDocuments\Devices\Properties\Dynamic(
				$id,
				Uuid\Uuid::fromString(self::DEVICE),
				DevicesTypes\PropertyCategory::GENERIC,
				'property',
				null,
				$dataType,
				format: $format,
				valueTransformer: $transformer,
				settable: $settable,
			);
		}

		return new DevicesDocuments\Channels\Properties\Dynamic(
			$id,
			Uuid\Uuid::fromString(self::CHANNEL),
			DevicesTypes\PropertyCategory::GENERIC,
			'property',
			null,
			$dataType,
			format: $format,
			valueTransformer: $transformer,
			settable: $settable,
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
