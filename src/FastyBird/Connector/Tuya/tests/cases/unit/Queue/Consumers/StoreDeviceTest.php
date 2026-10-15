<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
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
 * What storing a discovered device writes, field by field: the device with its variable properties, the state property
 * the device subscriber adds, its parent when it sits behind a gateway, and its channel with the data points. A second
 * message for the same device takes the update paths.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreDeviceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CLOUD_CONNECTOR_ID = '74fba85a-9585-4f5f-a7cc-8d3806da5ada';

	private const string LOCAL_CONNECTOR_ID = '27848e3c-23b4-4aaa-a94e-aae975d98550';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreCloudDevice(): void
	{
		$this->withoutStateStorage();

		$this->consume(Queue\Consumers\StoreCloudDevice::class, Queue\Messages\StoreCloudDevice::class, [
			'connector' => Uuid\Uuid::fromString(self::CLOUD_CONNECTOR_ID),
			'id' => 'bf3e9d85a52b163f940wgx',
			'ip_address' => '10.10.10.10',
			'local_key' => 'local-key',
			'name' => 'Cloud device',
			'model' => 'model-1',
			'icon' => 'smart/icon/ay1525749833414yotNt/b8d2d2f8a3e0d41ce6f7e6be5f8e0e3c.png',
			'category' => 'kg',
			'product_id' => 'product-1',
			'product_name' => 'Wall switch',
			'latitude' => '50.08',
			'longitude' => '14.43',
			'sn' => 'sn-1',
			'mac' => 'aa:bb:cc:dd:ee:ff',
			'data_points' => [
				[
					'device' => 'bf3e9d85a52b163f940wgx',
					'code' => 'switch_1',
					'name' => 'Switch 1',
					'data_type' => 'bool',
					'unit' => null,
					'range' => [],
					'min' => null,
					'max' => null,
					'step' => null,
					'scale' => null,
					'queryable' => true,
					'settable' => true,
				],
				[
					'device' => 'bf3e9d85a52b163f940wgx',
					'code' => 'countdown_1',
					'name' => 'Countdown 1',
					'data_type' => 'int',
					'unit' => 's',
					'range' => [],
					'min' => 0,
					'max' => 86_400,
					'step' => 1,
					'scale' => 0,
					'queryable' => true,
					'settable' => true,
				],
				[
					'device' => 'bf3e9d85a52b163f940wgx',
					'code' => 'relay_status',
					'name' => 'Relay status',
					'data_type' => 'enum',
					'unit' => null,
					'range' => ['off', 'on', 'memory'],
					'min' => null,
					'max' => null,
					'step' => null,
					'scale' => null,
					'queryable' => true,
					'settable' => true,
				],
			],
		]);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'bf3e9d85a52b163f940wgx',
				'name' => 'Cloud device',
				'comment' => null,
				'connector' => 'tuya-cloud',
				'parents' => [],
				'properties' => [
					'category' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'category',
						'name' => 'Category',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'kg',
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
						'value' => 'aa:bb:cc:dd:ee:ff',
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
						'value' => 'model-1',
						'children' => [],
					],
					'icon' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'icon',
						'name' => 'Icon',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'smart/icon/ay1525749833414yotNt/b8d2d2f8a3e0d41ce6f7e6be5f8e0e3c.png',
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
						'value' => '10.10.10.10',
						'children' => [],
					],
					'lat' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'lat',
						'name' => 'Lat',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '50.08',
						'children' => [],
					],
					'local_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'local_key',
						'name' => 'Local key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local-key',
						'children' => [],
					],
					'lon' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'lon',
						'name' => 'Lon',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '14.43',
						'children' => [],
					],
					'product_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_id',
						'name' => 'Product ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'product-1',
						'children' => [],
					],
					'product_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_name',
						'name' => 'Product name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Wall switch',
						'children' => [],
					],
					'serial_number' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'serial_number',
						'name' => 'Serial number',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'sn-1',
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
							'lost',
							'sleeping',
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
				],
				'controls' => [],
				'channels' => [
					'cloud' => [
						'type' => 'tuya-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							'countdown_1' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'countdown_1',
								'name' => 'countdown_1',
								'data_type' => 'int',
								'unit' => 's',
								'format' => [
									[
										'f',
										0.0,
									],
									[
										'f',
										86_400.0,
									],
								],
								'invalid' => null,
								'scale' => 0,
								'step' => 1.0,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
							'relay_status' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'relay_status',
								'name' => 'relay_status',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'on',
									'memory',
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
							'switch_1' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch_1',
								'name' => 'switch_1',
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
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
			Tests\Support\StoredEntities::device($this->device('bf3e9d85a52b163f940wgx')),
		);

		$this->consume(Queue\Consumers\StoreCloudDevice::class, Queue\Messages\StoreCloudDevice::class, [
			'connector' => Uuid\Uuid::fromString(self::CLOUD_CONNECTOR_ID),
			'id' => 'bf3e9d85a52b163f940wgx',
			'ip_address' => null,
			'local_key' => 'local-key-2',
			'name' => 'Cloud device renamed',
			'model' => null,
			'icon' => null,
			'category' => 'kg',
			'product_id' => 'product-1',
			'product_name' => 'Wall switch',
			'latitude' => null,
			'longitude' => null,
			'sn' => 'sn-1',
			'mac' => 'aa:bb:cc:dd:ee:ff',
			'data_points' => [
				[
					'device' => 'bf3e9d85a52b163f940wgx',
					'code' => 'countdown_1',
					'name' => 'Countdown 1',
					'data_type' => 'float',
					'unit' => 'min',
					'range' => [],
					'min' => 0.5,
					'max' => 1_440,
					'step' => 0.5,
					'scale' => 1,
					'queryable' => false,
					'settable' => true,
				],
				[
					'device' => 'bf3e9d85a52b163f940wgx',
					'code' => 'relay_status',
					'name' => 'Relay status',
					'data_type' => 'enum',
					'unit' => null,
					'range' => ['off', 'on'],
					'min' => null,
					'max' => null,
					'step' => null,
					'scale' => null,
					'queryable' => true,
					'settable' => false,
				],
			],
		]);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'bf3e9d85a52b163f940wgx',
				'name' => 'Cloud device',
				'comment' => null,
				'connector' => 'tuya-cloud',
				'parents' => [],
				'properties' => [
					'category' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'category',
						'name' => 'Category',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'kg',
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
						'value' => 'aa:bb:cc:dd:ee:ff',
						'children' => [],
					],
					'local_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'local_key',
						'name' => 'Local key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local-key-2',
						'children' => [],
					],
					'product_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_id',
						'name' => 'Product ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'product-1',
						'children' => [],
					],
					'product_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_name',
						'name' => 'Product name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Wall switch',
						'children' => [],
					],
					'serial_number' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'serial_number',
						'name' => 'Serial number',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'sn-1',
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
							'lost',
							'sleeping',
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
				],
				'controls' => [],
				'channels' => [
					'cloud' => [
						'type' => 'tuya-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							'countdown_1' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'countdown_1',
								'name' => 'countdown_1',
								'data_type' => 'float',
								'unit' => 'min',
								'format' => [
									[
										'f',
										0.5,
									],
									[
										'f',
										1_440.0,
									],
								],
								'invalid' => null,
								'scale' => 1,
								'step' => 0.5,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => false,
								'children' => [],
							],
							'relay_status' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'relay_status',
								'name' => 'relay_status',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'on',
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
							'switch_1' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'switch_1',
								'name' => 'switch_1',
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
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
			Tests\Support\StoredEntities::device($this->device('bf3e9d85a52b163f940wgx')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreLocalDeviceBehindAGateway(): void
	{
		$this->withoutStateStorage();

		$this->consume(
			Queue\Consumers\StoreLocalDevice::class,
			Queue\Messages\StoreLocalDevice::class,
			$this->localDevice('gateway-1', null, null, 'Gateway', []),
		);

		$this->consume(
			Queue\Consumers\StoreLocalDevice::class,
			Queue\Messages\StoreLocalDevice::class,
			$this->localDevice('child-1', 'gateway-1', 'node-1', 'Child', [
				[
					'device' => 'child-1',
					'code' => '1',
					'name' => 'switch_1',
					'data_type' => 'bool',
					'unit' => null,
					'format' => null,
					'min' => null,
					'max' => null,
					'step' => null,
					'queryable' => true,
					'settable' => true,
				],
				[
					'device' => 'child-1',
					'code' => '2',
					'name' => 'mode',
					'data_type' => 'enum',
					'unit' => null,
					'format' => 'white,colour,scene',
					'min' => null,
					'max' => null,
					'step' => null,
					'queryable' => true,
					'settable' => true,
				],
			]),
		);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'child-1',
				'name' => 'Child',
				'comment' => null,
				'connector' => 'tuya-local',
				'parents' => [
					'gateway-1',
				],
				'properties' => [
					'category' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'category',
						'name' => 'Category',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'dj',
						'children' => [],
					],
					'encrypted' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'encrypted',
						'name' => 'Encrypted',
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
					'gateway_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'gateway_id',
						'name' => 'Gateway ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'gateway-1',
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
						'value' => '10.10.10.20',
						'children' => [],
					],
					'local_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'local_key',
						'name' => 'Local key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local-key-child-1',
						'children' => [],
					],
					'node_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'node_id',
						'name' => 'Node ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'node-1',
						'children' => [],
					],
					'product_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_id',
						'name' => 'Product ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'product-child-1',
						'children' => [],
					],
					'product_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_name',
						'name' => 'Product name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Bulb',
						'children' => [],
					],
					'protocol_version' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'protocol_version',
						'name' => 'Protocol version',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'3.1',
							'3.2',
							'3.3',
							'3.4',
							'3.2+',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '3.3',
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
							'lost',
							'sleeping',
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
				],
				'controls' => [],
				'channels' => [
					'local' => [
						'type' => 'tuya-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							1 => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '1',
								'name' => '1',
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
							2 => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '2',
								'name' => '2',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'white',
									'colour',
									'scene',
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
			Tests\Support\StoredEntities::device($this->device('child-1')),
		);

		$this->consume(
			Queue\Consumers\StoreLocalDevice::class,
			Queue\Messages\StoreLocalDevice::class,
			$this->localDevice('child-1', 'gateway-1', 'node-2', 'Child renamed', [
				[
					'device' => 'child-1',
					'code' => '2',
					'name' => 'mode',
					'data_type' => 'enum',
					'unit' => null,
					'format' => 'white,colour',
					'min' => null,
					'max' => null,
					'step' => null,
					'queryable' => false,
					'settable' => true,
				],
			]),
		);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'child-1',
				'name' => 'Child',
				'comment' => null,
				'connector' => 'tuya-local',
				'parents' => [
					'gateway-1',
				],
				'properties' => [
					'category' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'category',
						'name' => 'Category',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'dj',
						'children' => [],
					],
					'encrypted' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'encrypted',
						'name' => 'Encrypted',
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
					'gateway_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'gateway_id',
						'name' => 'Gateway ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'gateway-1',
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
						'value' => '10.10.10.20',
						'children' => [],
					],
					'local_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'local_key',
						'name' => 'Local key',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local-key-child-1',
						'children' => [],
					],
					'node_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'node_id',
						'name' => 'Node ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'node-2',
						'children' => [],
					],
					'product_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_id',
						'name' => 'Product ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'product-child-1',
						'children' => [],
					],
					'product_name' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'product_name',
						'name' => 'Product name',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Bulb',
						'children' => [],
					],
					'protocol_version' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'protocol_version',
						'name' => 'Protocol version',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'3.1',
							'3.2',
							'3.3',
							'3.4',
							'3.2+',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '3.3',
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
							'lost',
							'sleeping',
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
				],
				'controls' => [],
				'channels' => [
					'local' => [
						'type' => 'tuya-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							1 => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '1',
								'name' => '1',
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
							2 => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '2',
								'name' => '2',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'white',
									'colour',
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
						'controls' => [],
					],
				],
			],
			Tests\Support\StoredEntities::device($this->device('child-1')),
		);
	}

	/**
	 * An updated data point drops the state stored for it, and the test container has no state storage
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function withoutStateStorage(): void
	{
		$states = $this->mockOf(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			static function (MockObject $mock): void {
				$mock->method('delete')->willReturn(Promise\resolve(true));
			},
		);

		$this->mockContainerService(DevicesModels\States\Async\ChannelPropertiesManager::class, $states);
	}

	/**
	 * @param array<int, array<string, mixed>> $dataPoints
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function localDevice(
		string $id,
		string|null $gateway,
		string|null $nodeId,
		string $name,
		array $dataPoints,
	): array
	{
		return [
			'connector' => Uuid\Uuid::fromString(self::LOCAL_CONNECTOR_ID),
			'id' => $id,
			'ip_address' => '10.10.10.20',
			'local_key' => 'local-key-' . $id,
			'encrypted' => true,
			'version' => '3.3',
			'gateway' => $gateway,
			'node_id' => $nodeId,
			'name' => $name,
			'model' => null,
			'icon' => null,
			'category' => 'dj',
			'product_id' => 'product-' . $id,
			'product_name' => 'Bulb',
			'latitude' => null,
			'longitude' => null,
			'sn' => null,
			'mac' => null,
			'data_points' => $dataPoints,
		];
	}

	/**
	 * @param class-string<Queue\Consumers\StoreCloudDevice|Queue\Consumers\StoreLocalDevice> $consumer
	 * @param class-string<Queue\Messages\StoreCloudDevice|Queue\Messages\StoreLocalDevice> $message
	 * @param array<string, mixed> $data
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function consume(string $consumer, string $message, array $data): void
	{
		$service = $this->getContainer()->getByType($consumer);
		assert($service instanceof Queue\Consumer);

		$consumed = $service->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create($message, $data),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(string $identifier): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier($identifier);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);

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
