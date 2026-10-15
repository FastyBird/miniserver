<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Queue\Consumers;

use Error;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use Throwable;
use function assert;
use function is_int;
use function is_string;

/**
 * A discovery run stores every device the cloud knows, with the address the search of the network found it at, or
 * with none when the search did not find it
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreDeviceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '63a28696-cbea-4757-84d7-156e52ade4d3';

	private const string DEVICE_ABC = '1e4b1d2f-6e3c-4b9f-ad8e-7c6b5a4f3e2d';

	#[Override]
	public function setUp(): void
	{
		// The files load in the reverse order they are registered in, so this one comes after the connectors
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../../sql/identifiers.data.sql');

		parent::setUp();
	}

	/**
	 * A device the search of the network did not find keeps the address it was stored with, and a device it found
	 * takes the address it was found at
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testDeviceStoredWithoutAnAddressKeepsTheAddressItWasStoredWith(): void
	{
		$this->store('10.0.0.7', 'eWeLink_abc.local', 8_081);

		self::assertSame(['10.0.0.7', 'eWeLink_abc.local', 8_081], $this->storedAddress());

		$this->store(null, null, null);

		self::assertSame(['10.0.0.7', 'eWeLink_abc.local', 8_081], $this->storedAddress());

		$this->store('10.0.0.9', 'eWeLink_abc.local', 8_082);

		self::assertSame(['10.0.0.9', 'eWeLink_abc.local', 8_082], $this->storedAddress());
	}

	/**
	 * What storing a device writes, field by field: the device with its variable properties, its dynamic properties
	 * and its channels with their dynamic properties. A second message for the same device takes the update paths.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoredDeviceFieldByField(): void
	{
		$this->storeMessage([
			'id' => 'pin-device',
			'apiKey' => 'api-key',
			'deviceKey' => 'device-key',
			'uiid' => 77,
			'name' => 'Pinned device',
			'description' => 'Pinned description',
			'brandName' => 'SONOFF',
			'brandLogo' => 'https://logo.example/sonoff.png',
			'productModel' => 'MINIR2',
			'model' => 'PSF-B04-GL',
			'mac' => 'd0:27:00:00:00:77',
			'ipAddress' => '10.0.0.77',
			'domain' => 'eWeLink_pin.local',
			'port' => 8_081,
			'parameters' => [
				[
					'group' => 'device',
					'identifier' => 'rssi',
					'name' => 'RSSI',
					'type' => 'device',
					'dataType' => 'char',
					'format' => [-100, 0],
					'settable' => false,
					'queryable' => true,
					'scale' => null,
				],
				[
					'group' => 'device',
					'identifier' => 'sledOnline',
					'name' => 'Status LED',
					'type' => 'device',
					'dataType' => 'enum',
					'format' => ['on', 'off'],
					'settable' => true,
					'queryable' => true,
					'scale' => null,
				],
				[
					'group' => 'switch_0',
					'identifier' => 'switch',
					'name' => 'Switch',
					'type' => 'channel',
					'dataType' => 'switch',
					'format' => [['sw_on', 'on', 'on'], ['sw_off', 'off', 'off']],
					'settable' => true,
					'queryable' => true,
					'scale' => null,
				],
				[
					'group' => 'switch_0',
					'identifier' => 'power',
					'name' => 'Power',
					'type' => 'channel',
					'dataType' => 'float',
					'format' => [0, 3_500.5],
					'settable' => false,
					'queryable' => true,
					'scale' => 2,
				],
			],
		]);

		self::assertSame(
			[
				'type' => 'sonoff-connector',
				'identifier' => 'pin-device',
				'name' => 'Pinned device',
				'comment' => null,
				'connector' => 'sonoff-local',
				'parents' => [],
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
						'value' => 'eWeLink_pin.local',
						'children' => [],
					],
					'api_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'api_key',
						'name' => 'Api key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'api-key',
						'children' => [],
					],
					'brand_logo' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'brand_logo',
						'name' => 'Brand logo',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'https://logo.example/sonoff.png',
						'children' => [],
					],
					'brand_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'brand_name',
						'name' => 'Brand name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'SONOFF',
						'children' => [],
					],
					'device_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'device_key',
						'name' => 'Device key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'device-key',
						'children' => [],
					],
					'hardware_mac_address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_mac_address',
						'name' => 'Hardware MAC address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'd0:27:00:00:00:77',
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
						'value' => 'PSF-B04-GL',
						'children' => [],
					],
					'ip_address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'ip_address',
						'name' => 'IP address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '10.0.0.77',
						'children' => [],
					],
					'port' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'port',
						'name' => 'Port',
						'data_type' => 'uint',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 8_081,
						'children' => [],
					],
					'product_model' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_model',
						'name' => 'Product model',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'MINIR2',
						'children' => [],
					],
					'rssi' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'rssi',
						'name' => 'RSSI',
						'data_type' => 'char',
						'unit' => null,
						'format' => [
							-100.0,
							0.0,
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'settable' => false,
						'queryable' => true,
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
					'status_led' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'status_led',
						'name' => 'Status LED',
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
						'settable' => true,
						'queryable' => true,
						'children' => [],
					],
					'uiid' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'uiid',
						'name' => 'Uiid',
						'data_type' => 'uchar',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 77,
						'children' => [],
					],
				],
				'controls' => [],
				'channels' => [
					'switch_0' => [
						'type' => 'sonoff-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							'power' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'power',
								'name' => 'Power',
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									0.0,
									3_500.5,
								],
								'invalid' => null,
								'scale' => 2,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'switch' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch',
								'name' => 'Switch',
								'data_type' => 'switch',
								'unit' => null,
								'format' => [
									[
										'sw_on',
										'on',
										'on',
									],
									[
										'sw_off',
										'off',
										'off',
									],
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
						'controls' => [],
					],
				],
			],
			Tests\Support\StoredEntities::device($this->pinDevice()),
		);

		$this->storeMessage([
			'id' => 'pin-device',
			'apiKey' => 'api-key-2',
			'deviceKey' => 'device-key-2',
			'uiid' => 78,
			'name' => 'Renamed device',
			'description' => null,
			'brandName' => 'SONOFF',
			'brandLogo' => null,
			'productModel' => 'MINIR3',
			'model' => 'PSF-B04-GL',
			'mac' => 'd0:27:00:00:00:77',
			'ipAddress' => '10.0.0.78',
			'domain' => 'eWeLink_pin.local',
			'port' => 8_082,
			'parameters' => [
				[
					'group' => 'device',
					'identifier' => 'rssi',
					'name' => 'Signal',
					'type' => 'device',
					'dataType' => 'short',
					'format' => [-120, 10],
					'settable' => false,
					'queryable' => false,
					'scale' => 1,
				],
				[
					'group' => 'switch_0',
					'identifier' => 'switch',
					'name' => 'Relay',
					'type' => 'channel',
					'dataType' => 'enum',
					'format' => ['on', 'off', 'toggle'],
					'settable' => false,
					'queryable' => true,
					'scale' => null,
				],
			],
		]);

		self::assertSame(
			[
				'type' => 'sonoff-connector',
				'identifier' => 'pin-device',
				'name' => 'Pinned device',
				'comment' => null,
				'connector' => 'sonoff-local',
				'parents' => [],
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
						'value' => 'eWeLink_pin.local',
						'children' => [],
					],
					'api_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'api_key',
						'name' => 'Api key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'api-key-2',
						'children' => [],
					],
					'brand_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'brand_name',
						'name' => 'Brand name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'SONOFF',
						'children' => [],
					],
					'device_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'device_key',
						'name' => 'Device key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'device-key-2',
						'children' => [],
					],
					'hardware_mac_address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'hardware_mac_address',
						'name' => 'Hardware MAC address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'd0:27:00:00:00:77',
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
						'value' => 'PSF-B04-GL',
						'children' => [],
					],
					'ip_address' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'ip_address',
						'name' => 'IP address',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '10.0.0.78',
						'children' => [],
					],
					'port' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'port',
						'name' => 'Port',
						'data_type' => 'uint',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 8_082,
						'children' => [],
					],
					'product_model' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_model',
						'name' => 'Product model',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'MINIR3',
						'children' => [],
					],
					'rssi' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'rssi',
						'name' => 'Signal',
						'data_type' => 'short',
						'unit' => null,
						'format' => [
							-120.0,
							10.0,
						],
						'invalid' => null,
						'scale' => 1,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'settable' => false,
						'queryable' => false,
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
					'status_led' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'status_led',
						'name' => 'Status LED',
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
						'settable' => true,
						'queryable' => true,
						'children' => [],
					],
					'uiid' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'uiid',
						'name' => 'Uiid',
						'data_type' => 'uchar',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 78,
						'children' => [],
					],
				],
				'controls' => [],
				'channels' => [
					'switch_0' => [
						'type' => 'sonoff-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							'power' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'power',
								'name' => 'Power',
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									0.0,
									3_500.5,
								],
								'invalid' => null,
								'scale' => 2,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'switch' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch',
								'name' => 'Switch',
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
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
						],
						'controls' => [],
					],
				],
			],
			Tests\Support\StoredEntities::device($this->pinDevice()),
		);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storeMessage(array $data): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDevice::class,
				['connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID)] + $data,
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function pinDevice(): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('pin-device');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);

		return $device;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function store(string|null $ipAddress, string|null $domain, int|null $port): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'id' => 'abc',
					'apiKey' => 'api-key',
					'deviceKey' => 'device-key',
					'uiid' => 1,
					'name' => 'Shorter identifier',
					'description' => null,
					'brandName' => 'SONOFF',
					'brandLogo' => null,
					'productModel' => 'BASICR2',
					'model' => 'PSF-BD1-GL',
					'mac' => 'd0:27:00:00:00:01',
					'ipAddress' => $ipAddress,
					'domain' => $domain,
					'port' => $port,
					'parameters' => [],
				],
			),
		);

		self::assertTrue($consumed);
	}

	/**
	 * @return array{string|null, string|null, int|null}
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storedAddress(): array
	{
		$ipAddress = $this->storedValue(Types\DevicePropertyIdentifier::IP_ADDRESS);
		$domain = $this->storedValue(Types\DevicePropertyIdentifier::ADDRESS);
		$port = $this->storedValue(Types\DevicePropertyIdentifier::PORT);

		assert($ipAddress === null || is_string($ipAddress));
		assert($domain === null || is_string($domain));
		assert($port === null || is_int($port));

		return [$ipAddress, $domain, $port];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storedValue(Types\DevicePropertyIdentifier $identifier): mixed
	{
		$findPropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findPropertyQuery->byDeviceId(Uuid\Uuid::fromString(self::DEVICE_ABC));
		$findPropertyQuery->byIdentifier($identifier);

		return $this->getContainer()
			->getByType(DevicesModels\Entities\Devices\Properties\PropertiesRepository::class)
			->findOneBy($findPropertyQuery)
			?->getValue();
	}

}
