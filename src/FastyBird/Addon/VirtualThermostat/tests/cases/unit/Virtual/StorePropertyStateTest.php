<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Cases\Unit\Virtual;

use Closure;
use Error;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Tests;
use FastyBird\Connector\Virtual\Helpers as VirtualHelpers;
use FastyBird\Connector\Virtual\Queue as VirtualQueue;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function assert;

/**
 * What the Virtual connector's consumers store for a reported property state of a thermostat, which is a Virtual
 * device: what they hand the state managers for a dynamic property and for a property mapped to a dynamic one, and
 * what they store for a variable property and for a property mapped to a variable one, on the device and on a
 * channel. The Virtual connector's own tests have no concrete device type, so the thermostat stands in.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StorePropertyStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '2b1ce81f-9933-4d52-afd4-bec3583e6a06';

	private const string DEVICE_ID = '552cea8a-0e81-41d9-be2f-839b079f315e';

	private const string STATE_CHANNEL_ID = 'b453987e-bbf4-46fc-830f-6448b19d9665';

	private const string CONFIGURATION_CHANNEL_ID = 'c2c572b3-3248-44da-aca0-fd329e1d9418';

	/** @var list<array<mixed>> */
	private array $calls = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreDevicePropertyState(): void
	{
		$this->recordStates();

		$devicesProperties = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesRepository::class,
		);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->find(
			Uuid\Uuid::fromString(self::DEVICE_ID),
		);
		assert($device !== null);

		$manager = $this->getContainer()->getByType(DevicesModels\Entities\Devices\Properties\PropertiesManager::class);

		$mappedDynamic = $manager->create([
			'entity' => DevicesEntities\Devices\Properties\Mapped::class,
			'device' => $device,
			'parent' => $devicesProperties->find(Uuid\Uuid::fromString('c24e65c8-610a-437d-b709-7de3127695e3')),
			'identifier' => 'mapped_state',
			'dataType' => ValuesTypes\DataType::ENUM,
			'format' => ['connected', 'disconnected', 'alert', 'unknown'],
		]);

		$mappedVariable = $manager->create([
			'entity' => DevicesEntities\Devices\Properties\Mapped::class,
			'device' => $device,
			'parent' => $devicesProperties->find(Uuid\Uuid::fromString('4412b0ea-afba-41c3-a9d0-7d3b04abf61c')),
			'identifier' => 'mapped_mac_address',
			'dataType' => ValuesTypes\DataType::STRING,
		]);

		$this->getEntityManager()->clear();

		$consumer = $this->getContainer()->getByType(VirtualQueue\Consumers\StoreDevicePropertyState::class);

		foreach ([
			// dynamic, by identifier
			['state', 'connected'],
			// mapped to a dynamic one, by id
			[$mappedDynamic->getId()->toString(), 'alert'],
			// variable, by identifier
			['hardware_model', 'pinned-model'],
			// mapped to a variable one, by id
			[$mappedVariable->getId()->toString(), '00:11:22:33:44:55'],
		] as [$property, $value]) {
			self::assertTrue($consumer->consume($this->message(VirtualQueue\Messages\StoreDevicePropertyState::class, [
				'connector' => self::CONNECTOR_ID,
				'device' => self::DEVICE_ID,
				'property' => $property,
				'value' => $value,
				'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
			])));
		}

		self::assertSame(
			[
				[
					'set',
					'device',
					'state',
					[DevicesStates\Property::ACTUAL_VALUE_FIELD => 'connected'],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'write',
					'device',
					'mapped_state',
					[DevicesStates\Property::EXPECTED_VALUE_FIELD => 'alert'],
					'com.fastybird.virtual-thermostat-addon',
				],
			],
			$this->calls,
		);

		$this->getEntityManager()->clear();

		self::assertSame('pinned-model', $this->variable($devicesProperties, '580a6d7c-4517-4821-a656-2810ae876898'));
		self::assertSame(
			'00:11:22:33:44:55',
			$this->variable($devicesProperties, '4412b0ea-afba-41c3-a9d0-7d3b04abf61c'),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreChannelPropertyState(): void
	{
		$this->recordStates();

		$channelsProperties = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesRepository::class,
		);

		$configuration = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\ChannelsRepository::class,
		)->find(
			Uuid\Uuid::fromString(self::CONFIGURATION_CHANNEL_ID),
		);
		assert($configuration !== null);

		$mappedVariable = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesManager::class,
		)->create([
			'entity' => DevicesEntities\Channels\Properties\Mapped::class,
			'channel' => $configuration,
			'parent' => $channelsProperties->find(Uuid\Uuid::fromString('c07dbed5-1a6d-4a9c-bdc8-31fe5b77cccd')),
			'identifier' => 'mapped_high_tolerance',
			'dataType' => ValuesTypes\DataType::FLOAT,
		]);

		$this->getEntityManager()->clear();

		$consumer = $this->getContainer()->getByType(VirtualQueue\Consumers\StoreChannelPropertyState::class);

		foreach ([
			// dynamic, by identifier
			[self::STATE_CHANNEL_ID, 'hvac_mode', 'heat'],
			// mapped to a dynamic one, by id
			['29e4d707-142d-4224-9983-0e568f259639', 'd58fe894-0d1c-4bf0-bff5-a190cab20e5c', 22.5],
			// variable, by identifier
			[self::CONFIGURATION_CHANNEL_ID, 'low_target_temperature_tolerance', 0.4],
			// mapped to a variable one, by id
			[self::CONFIGURATION_CHANNEL_ID, $mappedVariable->getId()->toString(), 0.6],
		] as [$channel, $property, $value]) {
			self::assertTrue($consumer->consume($this->message(VirtualQueue\Messages\StoreChannelPropertyState::class, [
				'connector' => self::CONNECTOR_ID,
				'device' => self::DEVICE_ID,
				'channel' => $channel,
				'property' => $property,
				'value' => $value,
				'source' => Sources\Addon::VIRTUAL_THERMOSTAT,
			])));
		}

		self::assertSame(
			[
				[
					'set',
					'channel',
					'hvac_mode',
					[DevicesStates\Property::ACTUAL_VALUE_FIELD => 'heat'],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'write',
					'channel',
					'room_temperature_sensor_1',
					[DevicesStates\Property::EXPECTED_VALUE_FIELD => 22.5],
					'com.fastybird.virtual-thermostat-addon',
				],
			],
			$this->calls,
		);

		$this->getEntityManager()->clear();

		self::assertSame(0.4, $this->variable($channelsProperties, 'b814bc0c-9ce5-4f2d-82cc-f21e6b0e20f0'));
		self::assertSame(0.6, $this->variable($channelsProperties, 'c07dbed5-1a6d-4a9c-bdc8-31fe5b77cccd'));
	}

	/**
	 * The state managers are replaced by recorders of what set() and write() are handed. A PropertyUpdate is recorded as the array of its fields.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		$record = fn (string $method, string $owner): Closure => function (
			DevicesDocuments\Property $property,
			DevicesStates\PropertyUpdate $data,
			Sources\Source|null $source,
		) use (
			$method,
			$owner,
): Promise\PromiseInterface {
			$this->calls[] = [$method, $owner, $property->getIdentifier(), $data->toArray(), $source?->value];

			return Promise\resolve(true);
		};

		$this->mockContainerService(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\DevicePropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('set', 'device'));
					$mock->method('write')->willReturnCallback($record('write', 'device'));
				},
			),
		);

		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('set', 'channel'));
					$mock->method('write')->willReturnCallback($record('write', 'channel'));
				},
			),
		);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	private function message(string $class, array $data): VirtualQueue\Messages\Message
	{
		assert(
			$class === VirtualQueue\Messages\StoreDevicePropertyState::class || $class === VirtualQueue\Messages\StoreChannelPropertyState::class,
		);

		return $this->getContainer()->getByType(VirtualHelpers\MessageBuilder::class)->create($class, $data);
	}

	/**
	 * @throws Throwable
	 */
	private function variable(
		DevicesModels\Entities\Devices\Properties\PropertiesRepository|DevicesModels\Entities\Channels\Properties\PropertiesRepository $repository,
		string $id,
	): mixed
	{
		$property = $repository->find(Uuid\Uuid::fromString($id));

		self::assertTrue(
			$property instanceof DevicesEntities\Devices\Properties\Variable
			|| $property instanceof DevicesEntities\Channels\Properties\Variable,
		);

		return $property->getValue();
	}

	/**
	 * The services replaced are final, which PHPStan does not let a mock of one be typed as, so the mock is handed over
	 * as the class it stands in for
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 * @param Closure(MockObject): void $configure
	 *
	 * @return T
	 */
	private function mockOf(string $class, Closure $configure): object
	{
		$mock = $this->createMock($class);

		assert($mock instanceof $class);

		$configure($mock);

		return $mock;
	}

}
