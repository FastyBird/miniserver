<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Queue\Consumers;

use Error;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;
use Throwable;

/**
 * What storing a discovered device writes, field by field: the device with its variable properties, the state property
 * the device subscriber adds, and its channels with their dynamic properties. A second message for the same device
 * takes the update paths.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreLocalDeviceTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '86d6a4ba-6292-4947-b8c1-8681569ac28e';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreAndRestoreDevice(): void
	{
		$this->store([
			'serial_number' => 'c45bbee4c926',
			'generation' => 'gen1',
			'ip_address' => '10.10.0.239',
			'domain' => 'shellyrgbw2-C45BBEE4C926.local',
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
							'data_type' => 'switch',
							'format' => [
								[['sw', 'switch_on'], ['b', true], ['s', 'on']],
								[['sw', 'switch_off'], ['b', false], ['s', 'off']],
								[['sw', 'switch_toggle'], null, ['s', 'toggle']],
							],
							'queryable' => true,
							'settable' => true,
						],
						[
							'identifier' => '5101_brightness',
							'name' => 'brightness',
							'data_type' => 'uchar',
							'unit' => '%',
							'format' => [0, 100],
							'queryable' => true,
							'settable' => true,
						],
						[
							'identifier' => '4101_power',
							'name' => 'power',
							'data_type' => 'float',
							'unit' => 'W',
							'invalid' => -1,
							'queryable' => true,
						],
					],
				],
			],
		]);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'c45bbee4c926-shellyrgbw2',
				'name' => null,
				'comment' => null,
				'connector' => 'shelly-local',
				'parents' => [],
				'properties' => [
					'auth_enabled' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'auth_enabled',
						'name' => 'Auth enabled',
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
					'domain' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'domain',
						'name' => 'Domain',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'shellyrgbw2-C45BBEE4C926.local',
						'children' => [],
					],
					'firmware_version' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'firmware_version',
						'name' => 'Firmware version',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '20230913-114008/v1.14.0-gcb84623',
						'children' => [],
					],
					'generation' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'generation',
						'name' => 'Generation',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'gen1',
							'gen2',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'gen1',
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
						'value' => 'c4:5b:be:e4:c9:26',
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
						'value' => 'SHRGBW2',
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
						'value' => '10.10.0.239',
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
						'value' => 'c45bbee4c926',
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
					'1_light_0' => [
						'type' => 'shelly-connector',
						'name' => 'light_0',
						'comment' => null,
						'properties' => [
							'1101_output' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '1101_output',
								'name' => 'output',
								'data_type' => 'switch',
								'unit' => null,
								'format' => [
									[
										[
											'sw',
											'switch_on',
										],
										[
											'b',
											true,
										],
										[
											's',
											'on',
										],
									],
									[
										[
											'sw',
											'switch_off',
										],
										[
											'b',
											false,
										],
										[
											's',
											'off',
										],
									],
									[
										[
											'sw',
											'switch_toggle',
										],
										null,
										[
											's',
											'toggle',
										],
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
							'4101_power' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '4101_power',
								'name' => 'power',
								'data_type' => 'float',
								'unit' => 'W',
								'format' => null,
								'invalid' => -1.0,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'5101_brightness' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '5101_brightness',
								'name' => 'brightness',
								'data_type' => 'uchar',
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
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
						'controls' => [],
					],
				],
			],
			Tests\Support\StoredEntities::device($this->device()),
		);

		// The same device found again: an address, a model and a firmware changed, the domain is gone, and the channel
		// lost a property and changed another
		$this->store([
			'serial_number' => 'c45bbee4c926',
			'generation' => 'gen1',
			'ip_address' => '10.10.0.240',
			'domain' => null,
			'model' => 'SHRGBW2-2',
			'mac_address' => 'c4:5b:be:e4:c9:26',
			'auth_enabled' => true,
			'firmware_version' => '20240101-000000/v1.15.0',
			'channels' => [
				[
					'identifier' => '1_light_0',
					'name' => 'Light zero',
					'properties' => [
						[
							'identifier' => '1101_output',
							'name' => 'Output',
							'data_type' => 'enum',
							'format' => ['on', 'off'],
							'queryable' => true,
							'settable' => false,
						],
						[
							'identifier' => '5101_brightness',
							'name' => 'Brightness',
							'data_type' => 'char',
							'format' => [-10, 10],
							'invalid' => -128,
							'settable' => true,
						],
					],
				],
			],
		]);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'c45bbee4c926-shellyrgbw2',
				'name' => null,
				'comment' => null,
				'connector' => 'shelly-local',
				'parents' => [],
				'properties' => [
					'auth_enabled' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'auth_enabled',
						'name' => 'Auth enabled',
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
					'firmware_version' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'firmware_version',
						'name' => 'Firmware version',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '20240101-000000/v1.15.0',
						'children' => [],
					],
					'generation' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'generation',
						'name' => 'Generation',
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'gen1',
							'gen2',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'gen1',
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
						'value' => 'c4:5b:be:e4:c9:26',
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
						'value' => 'SHRGBW2-2',
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
						'value' => '10.10.0.240',
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
						'value' => 'c45bbee4c926',
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
					'1_light_0' => [
						'type' => 'shelly-connector',
						'name' => 'Light zero',
						'comment' => null,
						'properties' => [
							'1101_output' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '1101_output',
								'name' => 'Output',
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
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'5101_brightness' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => '5101_brightness',
								'name' => 'Brightness',
								'data_type' => 'char',
								'unit' => null,
								'format' => [
									-10.0,
									10.0,
								],
								'invalid' => -128,
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
			Tests\Support\StoredEntities::device($this->device()),
		);
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function store(array $data): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreLocalDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreLocalDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'c45bbee4c926-shellyrgbw2',
				] + $data,
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('c45bbee4c926-shellyrgbw2');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);

		return $device;
	}

}
