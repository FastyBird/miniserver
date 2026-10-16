<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Tests;
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
 * What storing a reported device state hands the state managers for a dynamic property, and what it stores for a
 * variable one, on the device and on a channel, found by a property identifier or by a channel's sensors
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreDeviceStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '86d6a4ba-6292-4947-b8c1-8681569ac28e';

	private const string IDENTIFIER = 'c45bbee4c926-shellyrgbw2';

	/** @var list<array<mixed>> */
	private array $set = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreDeviceState(): void
	{
		$this->recordStates();
		$this->storeDevice();

		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDeviceState::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDeviceState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => self::IDENTIFIER,
					'ip_address' => null,
					'states' => [
						// the device's own dynamic property
						['identifier' => 'state', 'value' => 'connected'],
						// the device's own variable property
						['identifier' => 'hardware_model', 'value' => 'SHRGBW2-X'],
						// a channel's dynamic property, found by its identifier
						['identifier' => '5101_brightness', 'value' => 42],
						// a channel's variable property, found by its identifier
						['identifier' => '9000_note', 'value' => 'first'],
						// a channel and its sensors
						[
							'identifier' => '1_light_0',
							'sensors' => [
								['identifier' => '4101_power', 'value' => 12.5],
								['identifier' => '1101_output', 'value' => true],
								['identifier' => '9000_note', 'value' => 'second'],
							],
						],
					],
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame(
			[
				['device', 'state', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 'connected'], 'com.fastybird.shelly-connector'],
				['channel', '5101_brightness', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 42.0], 'com.fastybird.shelly-connector'],
				['channel', '4101_power', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 12.5], 'com.fastybird.shelly-connector'],
				['channel', '1101_output', [DevicesStates\Property::ACTUAL_VALUE_FIELD => true], 'com.fastybird.shelly-connector'],
			],
			$this->set,
		);

		$this->getEntityManager()->clear();

		$device = $this->device();

		self::assertSame('SHRGBW2-X', $this->deviceProperty($device, 'hardware_model')->getValue());

		$channel = $device->getChannels()[0];

		foreach ($channel->getProperties() as $property) {
			if ($property->getIdentifier() === '9000_note') {
				self::assertInstanceOf(DevicesEntities\Channels\Properties\Variable::class, $property);
				// the sensor comes after the property, so it is the one that stays
				self::assertSame('second', $property->getValue());
			}
		}
	}

	/**
	 * The state managers are replaced by recorders of what set() is handed. A PropertyUpdate is recorded as the array of its fields.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		$record = fn (string $owner): Closure => function (
			DevicesDocuments\Property $property,
			DevicesStates\PropertyUpdate $data,
			Sources\Source|null $source,
		) use ($owner): Promise\PromiseInterface {
				$this->set[] = [$owner, $property->getIdentifier(), $data->toArray(), $source?->value];

				return Promise\resolve(true);
		};

		$devices = $this->mockOf(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			static function (MockObject $mock) use ($record): void {
				$mock->method('set')->willReturnCallback($record('device'));
			},
		);

		$channels = $this->mockOf(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			static function (MockObject $mock) use ($record): void {
				$mock->method('set')->willReturnCallback($record('channel'));
			},
		);

		$this->mockContainerService(DevicesModels\States\Async\DevicePropertiesManager::class, $devices);
		$this->mockContainerService(DevicesModels\States\Async\ChannelPropertiesManager::class, $channels);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storeDevice(): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreLocalDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreLocalDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => self::IDENTIFIER,
					'serial_number' => 'c45bbee4c926',
					'generation' => 'gen1',
					'ip_address' => '10.10.0.239',
					'domain' => null,
					'model' => 'SHRGBW2',
					'mac_address' => 'c4:5b:be:e4:c9:26',
					'auth_enabled' => false,
					'firmware_version' => '20230913-114008/v1.14.0-gcb84623',
					'channels' => [
						[
							'identifier' => '1_light_0',
							'name' => 'light_0',
							'properties' => [
								[
									'identifier' => '1101_output',
									'name' => 'output',
									'data_type' => 'bool',
									'queryable' => true,
									'settable' => true,
								],
								[
									'identifier' => '5101_brightness',
									'name' => 'brightness',
									'data_type' => 'uchar',
									'format' => [0, 100],
									'queryable' => true,
									'settable' => true,
								],
								[
									'identifier' => '4101_power',
									'name' => 'power',
									'data_type' => 'float',
									'queryable' => true,
								],
							],
						],
					],
				],
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();

		$this->getContainer()->getByType(DevicesModels\Entities\Channels\Properties\PropertiesManager::class)->create([
			'entity' => DevicesEntities\Channels\Properties\Variable::class,
			'channel' => $this->device()->getChannels()[0],
			'identifier' => '9000_note',
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => 'none',
		]);

		$this->getEntityManager()->clear();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): ShellyEntities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier(self::IDENTIFIER);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			ShellyEntities\Devices\Device::class,
		);

		self::assertInstanceOf(ShellyEntities\Devices\Device::class, $device);

		return $device;
	}

	private function deviceProperty(
		ShellyEntities\Devices\Device $device,
		string $identifier,
	): DevicesEntities\Devices\Properties\Variable
	{
		foreach ($device->getProperties() as $property) {
			if ($property->getIdentifier() === $identifier) {
				self::assertInstanceOf(DevicesEntities\Devices\Properties\Variable::class, $property);

				return $property;
			}
		}

		self::fail('Device property ' . $identifier . ' is missing');
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
