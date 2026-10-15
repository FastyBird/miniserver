<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Zigbee2Mqtt\Entities as Zigbee2MqttEntities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_map;
use function assert;
use function ksort;

/**
 * What storing the bridge's device list stores for a sub-device, field by field, the first time and again, and what
 * storing the sub-device's state hands the state manager
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreSubDeviceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = 'f15d2072-fb60-421a-a85f-2566e4dc13fe';

	private const string IEEE_ADDRESS = '0xa4c138f06eafa3da';

	/** @var list<array<mixed>> */
	private array $set = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreSubDeviceAndItsState(): void
	{
		$this->recordStates();

		$this->storeDevices();

		$stored = $this->stored($this->subDevice());

		self::assertSame(
			[
				'type' => 'zigbee2mqtt-connector-sub-device',
				'identifier' => '0xa4c138f06eafa3da',
				'name' => 'Some sensor',
				'connector' => 'zigbee2mqtt',
				'parents' => [
					'bridge',
				],
				'properties' => [
					'address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'address',
						'name' => 'Address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '0xa4c138f06eafa3da',
						'children' => [],
					],
					'disabled' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'disabled',
						'name' => 'Disabled',
						'data_type' => 'bool',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => false,
						'children' => [],
					],
					'friendly_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'friendly_name',
						'name' => 'Friendly name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Pinned sensor',
						'children' => [],
					],
					'hardware_manufacturer' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_manufacturer',
						'name' => 'Hardware manufacturer',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'VendorName',
						'children' => [],
					],
					'hardware_model' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_model',
						'name' => 'Hardware model',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'SEN123',
						'children' => [],
					],
					'state' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => 'State',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'connected',
							'disconnected',
							'alert',
							'unknown',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'settable' => false,
						'queryable' => false,
						'children' => [],
					],
					'supported' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'supported',
						'name' => 'Supported',
						'data_type' => 'bool',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => true,
						'children' => [],
					],
					'type' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'type',
						'name' => 'Type',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'EndDevice',
						'children' => [],
					],
				],
				'channels' => [
					'binary_battery_low' => [
						'type' => 'zigbee2mqtt-connector',
						'name' => 'Battery low',
						'properties' => [
							'battery_low' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'battery_low',
								'name' => 'Battery low',
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => false,
								'children' => [],
							],
						],
					],
					'composite_day_time' => [
						'type' => 'zigbee2mqtt-connector',
						'name' => 'Day time',
						'properties' => [
							'day' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'day',
								'name' => 'Day',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'monday',
									'tuesday',
									'wednesday',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => false,
								'children' => [],
							],
						],
					],
					'numeric_battery' => [
						'type' => 'zigbee2mqtt-connector',
						'name' => 'Battery',
						'properties' => [
							'battery' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'battery',
								'name' => 'Battery',
								'data_type' => 'char',
								'unit' => '%',
								'format' => [
									0.0,
									100.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => false,
								'children' => [],
							],
						],
					],
				],
			],
			$stored,
		);

		// the second time, every property is there already and is updated
		$this->storeDevices();

		self::assertSame($stored, $this->stored($this->subDevice()));

		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDeviceState::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDeviceState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'base_topic' => 'zigbee2mqtt',
					'device' => self::IEEE_ADDRESS,
					'states' => [
						['type' => 'single', 'identifier' => 'battery_low', 'value' => true],
						['type' => 'single', 'identifier' => 'battery', 'value' => 87],
						[
							'type' => 'composite',
							'identifier' => 'day_time',
							'states' => [
								['type' => 'single', 'identifier' => 'day', 'value' => 'tuesday'],
							],
						],
					],
				],
			),
		);

		self::assertTrue($consumed);

		// the composite's member has no channel of its own, so only the two single values are stored
		self::assertSame(
			[
				[
					'battery_low',
					[
						'actual_value' => true,
					],
					'com.fastybird.zigbee2mqtt-connector',
				],
				[
					'battery',
					[
						'actual_value' => 87,
					],
					'com.fastybird.zigbee2mqtt-connector',
				],
			],
			$this->set,
		);
	}

	/**
	 * @throws Throwable
	 */
	private function storeDevices(): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreBridgeDevices::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreBridgeDevices::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'base_topic' => 'zigbee2mqtt',
					'devices' => [
						[
							'friendly_name' => 'Pinned sensor',
							'ieee_address' => self::IEEE_ADDRESS,
							'network_address' => 37_167,
							'interview_completed' => true,
							'interviewing' => false,
							'type' => 'EndDevice',
							'definition' => [
								'model' => 'SEN123',
								'vendor' => 'VendorName',
								'description' => 'Some sensor',
								'exposes' => [
									[
										'access' => 1,
										'description' => 'Indicates if the battery of this device is almost empty',
										'label' => 'Battery low',
										'name' => 'battery_low',
										'property' => 'battery_low',
										'type' => 'binary',
										'value_off' => false,
										'value_on' => true,
									],
									[
										'access' => 1,
										'description' => 'Remaining battery in %',
										'label' => 'Battery',
										'name' => 'battery',
										'property' => 'battery',
										'type' => 'numeric',
										'unit' => '%',
										'value_max' => 100,
										'value_min' => 0,
									],
									[
										'label' => 'Day time',
										'name' => 'day_time',
										'property' => 'day_time',
										'type' => 'composite',
										'features' => [
											[
												'access' => 3,
												'label' => 'Day',
												'name' => 'day',
												'property' => 'day',
												'type' => 'enum',
												'values' => ['monday', 'tuesday', 'wednesday'],
											],
										],
									],
								],
								'supports_ota' => false,
							],
							'description' => null,
							'manufacturer' => null,
							'model_id' => 'SEN123',
							'supported' => true,
							'disabled' => false,
						],
					],
				],
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * The state manager is replaced by a recorder of what set() is handed. A PropertyUpdate is read the way the manager
	 * reads it on its first line, so the record is the same for it and for the ArrayHash it replaced.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				function (MockObject $mock): void {
					$mock
						->method('set')
						->willReturnCallback(function (
							DevicesDocuments\Channels\Properties\Property $property,
							Utils\ArrayHash|DevicesStates\PropertyUpdate $data,
							Sources\Source|null $source,
						): Promise\PromiseInterface {
							$data = $data instanceof DevicesStates\PropertyUpdate ? $data->toArrayHash() : $data;

							$this->set[] = [$property->getIdentifier(), (array) $data, $source?->value];

							return Promise\resolve(true);
						});
					$mock->method('setValidState')->willReturn(Promise\resolve(true));
				},
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(Zigbee2MqttEntities\Devices\SubDevice $device): array
	{
		$channels = [];

		foreach ($device->getChannels() as $channel) {
			$channels[$channel->getIdentifier()] = [
				'type' => $channel::getType(),
				'name' => $channel->getName(),
				'properties' => $this->properties($channel->getProperties()),
			];
		}

		ksort($channels);

		return [
			'type' => $device::getType(),
			'identifier' => $device->getIdentifier(),
			'name' => $device->getName(),
			'connector' => $device->getConnector()->getIdentifier(),
			'parents' => array_map(
				static fn (DevicesEntities\Devices\Device $parent): string => $parent->getIdentifier(),
				$device->getParents(),
			),
			'properties' => $this->properties($device->getProperties()),
			'channels' => $channels,
		];
	}

	/**
	 * @param array<DevicesEntities\Property> $properties
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @throws Throwable
	 */
	private function properties(array $properties): array
	{
		$data = [];

		foreach ($properties as $property) {
			$item = $property->toArray();

			unset($item['id'], $item['created_at'], $item['updated_at'], $item['device'], $item['channel'], $item['owner']);

			$data[$property->getIdentifier()] = $item;
		}

		ksort($data);

		return $data;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function subDevice(): Zigbee2MqttEntities\Devices\SubDevice
	{
		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->byIdentifier(self::IEEE_ADDRESS);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Zigbee2MqttEntities\Devices\SubDevice::class,
		);

		self::assertInstanceOf(Zigbee2MqttEntities\Devices\SubDevice::class, $device);

		return $device;
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
