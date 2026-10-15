<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\FbMqtt\Entities as FbMqttEntities;
use FastyBird\Connector\FbMqtt\Exceptions as FbMqttExceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Tests;
use FastyBird\Connector\FbMqtt\Types;
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
use function sort;

/**
 * What the attribute and property consumers store for a device, field by field, and what they hand the state managers:
 * the device's name, properties, extensions, channels and controls, a channel's name, properties and controls, an
 * extension value, a property's configuration and a property's value. The device is the fixture's: the consumer cannot
 * create one, because it does not give a new device its connector.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConsumersTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '37b86cdc-376b-4d4c-9683-aa4f41daa13a';

	private const string DEVICE = 'first-device';

	/** @var list<array<mixed>> */
	private array $set = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testConsumeDeviceAndChannel(): void
	{
		$this->recordStates();

		$this->deviceAttribute('name', 'Pinned device');
		$this->deviceAttribute('properties', 'ip_address, uptime,status_led');
		$this->deviceAttribute('extensions', 'com.fastybird.hardware');
		$this->deviceAttribute('channels', 'channel-one,channel-a');
		$this->deviceAttribute('controls', 'configure,reboot');

		$this->channelAttribute('channel-a', 'name', 'Channel A');
		$this->channelAttribute('channel-a', 'properties', 'temperature,switch');
		$this->channelAttribute('channel-a', 'controls', 'configure');

		$this->consume(Queue\Consumers\ExtensionAttribute::class, Queue\Messages\ExtensionAttribute::class, [
			'connector' => self::CONNECTOR_ID,
			'device' => self::DEVICE,
			'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE,
			'parameter' => Queue\Messages\ExtensionAttribute::MANUFACTURER,
			'value' => 'FastyBird',
		]);

		// The property messages are built directly: their mapping rules for the attributes and the value are swapped,
		// so the message builder cannot make one
		$this->consumeMessage(Queue\Consumers\DeviceProperty::class, new Queue\Messages\DeviceProperty(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			self::DEVICE,
			'uptime',
			[
				new Queue\Messages\PropertyAttribute('name', 'Up time'),
				new Queue\Messages\PropertyAttribute('data-type', 'uint'),
				new Queue\Messages\PropertyAttribute('settable', 'false'),
				new Queue\Messages\PropertyAttribute('queryable', 'true'),
				new Queue\Messages\PropertyAttribute('unit', 's'),
			],
		));

		$this->consumeMessage(Queue\Consumers\DeviceProperty::class, new Queue\Messages\DeviceProperty(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			self::DEVICE,
			'uptime',
			value: '120',
		));

		$this->consumeMessage(Queue\Consumers\DeviceProperty::class, new Queue\Messages\DeviceProperty(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			self::DEVICE,
			'hardware_model',
			value: 'pin-model',
		));

		$this->consumeMessage(Queue\Consumers\ChannelProperty::class, new Queue\Messages\ChannelProperty(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			self::DEVICE,
			'channel-a',
			'temperature',
			[
				new Queue\Messages\PropertyAttribute('data-type', 'float'),
				new Queue\Messages\PropertyAttribute('format', '-10:50'),
				new Queue\Messages\PropertyAttribute('unit', '°C'),
			],
		));

		$this->consumeMessage(Queue\Consumers\ChannelProperty::class, new Queue\Messages\ChannelProperty(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			self::DEVICE,
			'channel-a',
			'temperature',
			value: '21.5',
		));

		self::assertSame(
			[
				[
					'device',
					'uptime',
					[
						'actual_value' => '120',
					],
					'com.fastybird.fb-mqtt-connector',
				],
				[
					'channel',
					'temperature',
					[
						'actual_value' => '21.5',
					],
					'com.fastybird.fb-mqtt-connector',
				],
			],
			$this->set,
		);

		self::assertSame(
			[
				'type' => 'fb-mqtt-connector',
				'identifier' => 'first-device',
				'name' => 'Pinned device',
				'connector' => 'fb-mqtt',
				'properties' => [
					'hardware_mac_address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_mac_address',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => null,
						'children' => [],
					],
					'hardware_manufacturer' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_manufacturer',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'FastyBird',
						'children' => [],
					],
					'hardware_model' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_model',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'pin-model',
						'children' => [],
					],
					'hardware_version' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_version',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => null,
						'children' => [],
					],
					'ip_address' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'ip_address',
						'name' => 'ip_address',
						'data_type' => 'string',
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
					'status_led' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'status_led',
						'name' => 'status_led',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'on',
							'off',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'on',
						'children' => [],
					],
					'uptime' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'uptime',
						'name' => 'Up time',
						'data_type' => 'int',
						'unit' => 's',
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'settable' => false,
						'queryable' => true,
						'children' => [],
					],
				],
				'controls' => [
					'configure',
					'reboot',
				],
				'channels' => [
					'channel-a' => [
						'type' => 'fb-mqtt-connector',
						'name' => 'Channel A',
						'properties' => [
							'switch' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch',
								'name' => null,
								'data_type' => 'unknown',
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
							'temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'temperature',
								'name' => null,
								'data_type' => 'unknown',
								'unit' => '°C',
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
						'controls' => [
							'configure',
						],
					],
					'channel-one' => [
						'type' => 'fb-mqtt-connector',
						'name' => 'Channel one',
						'properties' => [
							'switch' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch',
								'name' => 'switch',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'on',
									'off',
									'toggle',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
						'controls' => [
							'configure',
						],
					],
				],
			],
			$this->stored($this->device()),
		);
	}

	/**
	 * A second announcement of the lists keeps what it names and removes the rest
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testConsumeShorterLists(): void
	{
		$this->recordStates();

		$this->deviceAttribute('name', 'Pinned device');
		$this->deviceAttribute('properties', 'ip_address,uptime');
		$this->deviceAttribute('channels', 'channel-a,channel-b');
		$this->deviceAttribute('controls', 'configure,reboot');
		$this->channelAttribute('channel-a', 'properties', 'temperature,switch');
		$this->channelAttribute('channel-a', 'controls', 'configure,reset');

		$this->deviceAttribute('properties', 'uptime');
		$this->deviceAttribute('channels', 'channel-a');
		$this->deviceAttribute('controls', 'reboot');
		$this->channelAttribute('channel-a', 'properties', 'switch');
		$this->channelAttribute('channel-a', 'controls', 'reset');

		self::assertSame(
			[
				'type' => 'fb-mqtt-connector',
				'identifier' => 'first-device',
				'name' => 'Pinned device',
				'connector' => 'fb-mqtt',
				'properties' => [
					'uptime' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'uptime',
						'name' => 'uptime',
						'data_type' => 'int',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'settable' => false,
						'queryable' => true,
						'children' => [],
					],
				],
				'controls' => [
					'reboot',
				],
				'channels' => [
					'channel-a' => [
						'type' => 'fb-mqtt-connector',
						'name' => null,
						'properties' => [
							'switch' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch',
								'name' => null,
								'data_type' => 'unknown',
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
						'controls' => [
							'reset',
						],
					],
				],
			],
			$this->stored($this->device()),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function deviceAttribute(string $attribute, string $value): void
	{
		$this->consume(Queue\Consumers\DeviceAttribute::class, Queue\Messages\DeviceAttribute::class, [
			'connector' => self::CONNECTOR_ID,
			'device' => self::DEVICE,
			'attribute' => $attribute,
			'value' => $value,
		]);
	}

	/**
	 * @throws Throwable
	 */
	private function channelAttribute(string $channel, string $attribute, string $value): void
	{
		$this->consume(Queue\Consumers\ChannelAttribute::class, Queue\Messages\ChannelAttribute::class, [
			'connector' => self::CONNECTOR_ID,
			'device' => self::DEVICE,
			'channel' => $channel,
			'attribute' => $attribute,
			'value' => $value,
		]);
	}

	/**
	 * @param class-string<Queue\Consumer> $consumer
	 * @param class-string<Queue\Messages\Message> $message
	 * @param array<string, mixed> $data
	 *
	 * @throws Throwable
	 */
	private function consume(string $consumer, string $message, array $data): void
	{
		$this->consumeMessage(
			$consumer,
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create($message, $data),
		);
	}

	/**
	 * @param class-string<Queue\Consumer> $consumer
	 *
	 * @throws Throwable
	 */
	private function consumeMessage(string $consumer, Queue\Messages\Message $message): void
	{
		$service = $this->getContainer()->getByType($consumer);
		assert($service instanceof Queue\Consumer);

		self::assertTrue($service->consume($message));

		$this->getEntityManager()->clear();
	}

	/**
	 * The state managers are replaced by recorders of what set() is handed. A PropertyUpdate is read the way the
	 * managers read it on their first line, so the record is the same for it and for the ArrayHash it replaced.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		$record = fn (string $owner): Closure => function (
			DevicesDocuments\Property $property,
			Utils\ArrayHash|DevicesStates\PropertyUpdate $data,
			Sources\Source|null $source,
		) use ($owner): Promise\PromiseInterface {
			$data = $data instanceof DevicesStates\PropertyUpdate ? $data->toArrayHash() : $data;

			$this->set[] = [$owner, $property->getIdentifier(), (array) $data, $source?->value];

			return Promise\resolve(true);
		};

		$this->mockContainerService(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\DevicePropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('device'));
				},
			),
		);

		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('channel'));
				},
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(FbMqttEntities\Devices\Device $device): array
	{
		$channels = [];

		foreach ($device->getChannels() as $channel) {
			$channels[$channel->getIdentifier()] = [
				'type' => $channel::getType(),
				'name' => $channel->getName(),
				'properties' => $this->properties($channel->getProperties()),
				'controls' => $this->controls($channel->getControls()),
			];
		}

		ksort($channels);

		return [
			'type' => $device::getType(),
			'identifier' => $device->getIdentifier(),
			'name' => $device->getName(),
			'connector' => $device->getConnector()->getIdentifier(),
			'properties' => $this->properties($device->getProperties()),
			'controls' => $this->controls($device->getControls()),
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
	 * @param array<DevicesEntities\Devices\Controls\Control|DevicesEntities\Channels\Controls\Control> $controls
	 *
	 * @return array<string>
	 */
	private function controls(array $controls): array
	{
		$names = array_map(
			static fn (DevicesEntities\Devices\Controls\Control|DevicesEntities\Channels\Controls\Control $control): string => $control->getName(),
			$controls,
		);

		sort($names);

		return $names;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): FbMqttEntities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier(self::DEVICE);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			FbMqttEntities\Devices\Device::class,
		);

		self::assertInstanceOf(FbMqttEntities\Devices\Device::class, $device);

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
