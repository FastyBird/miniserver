<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Addon\VirtualThermostat\Commands;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Addon\VirtualThermostat\Exceptions as VirtualThermostatExceptions;
use FastyBird\Addon\VirtualThermostat\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function assert;
use function is_string;
use function ksort;

/**
 * What the installer stores for a thermostat, field by field, and the states it sets. The installer catches every
 * error and only prints it, so each case reads the database back instead of trusting the exit code.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class InstallTest extends Tests\Cases\Unit\DbTestCase
{

	/** @var list<array<mixed>> */
	private array $set = [];

	private bool $databaseKept = false;

	private bool $statesRecorded = false;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateThermostat(): void
	{
		$this->install([
			'0', // create a thermostat
			'', // the only connector
			'vt-pin', // identifier
			'Pinned thermostat', // name
			'0', // heating only
			'0', // celsius
			'0', '0', '0', // the heater: device, channel, property
			'no', // another heater
			'no', // opening sensors
			'0', '0', '0', // the room temperature sensor: device, channel, property
			'no', // another room temperature sensor
			'no', // floor temperature sensors
			'no', // room humidity sensors
			'0', // presets: away
			'18.5', // manual
			'21.5', // away
			'5', // nothing
		]);

		// the installer starts the thermostat off, in the manual preset, with each preset's target temperature
		self::assertSame(
			[
				[
					'state',
					'hvac_mode',
					[
						'actual_value' => 'off',
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'state',
					'preset_mode',
					[
						'actual_value' => 'manual',
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'preset_manual',
					'target_room_temperature',
					[
						'actual_value' => 18.5,
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'preset_away',
					'target_room_temperature',
					[
						'actual_value' => 21.5,
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
			],
			$this->set,
		);

		self::assertSame(
			[
				'type' => 'virtual-thermostat-addon',
				'identifier' => 'vt-pin',
				'name' => 'Pinned thermostat',
				'connector' => 'virtual',
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
						'value' => 'generated',
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
						'value' => 'virtual-thermostat-addon',
						'children' => [],
					],
					'state' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => null,
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'connected',
							'disconnected',
							'stopped',
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
				],
				'channels' => [
					'actors' => [
						'type' => 'virtual-thermostat-addon-actors',
						'name' => null,
						'properties' => [
							'heater_actor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'heater_actor_1',
								'name' => null,
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '11807caa-082c-468b-8a1b-88ba8a715ca1',
								'settable' => true,
								'queryable' => true,
							],
						],
					],
					'configuration' => [
						'type' => 'virtual-thermostat-addon-configuration',
						'name' => null,
						'properties' => [
							'high_target_temperature_tolerance' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'high_target_temperature_tolerance',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 0.3,
								'value_transformer' => null,
								'value' => 0.3,
								'children' => [],
							],
							'low_target_temperature_tolerance' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'low_target_temperature_tolerance',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 0.3,
								'value_transformer' => null,
								'value' => 0.3,
								'children' => [],
							],
							'unit' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'unit',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'°C',
									'°F',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'value' => '°C',
								'children' => [],
							],
						],
					],
					'preset_away' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 20.0,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'preset_manual' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 20.0,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'sensors' => [
						'type' => 'virtual-thermostat-addon-sensors',
						'name' => null,
						'properties' => [
							'room_temperature_sensor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'room_temperature_sensor_1',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '1e196c5c-a469-4ec7-95e7-c4bb48d58fe0',
								'settable' => false,
								'queryable' => true,
							],
						],
					],
					'state' => [
						'type' => 'virtual-thermostat-addon-state',
						'name' => null,
						'properties' => [
							'current_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'current_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									1_000.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'hvac_mode' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hvac_mode',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'heat',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'off',
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
							'hvac_state' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hvac_state',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'heating',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'off',
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'preset_mode' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'preset_mode',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'manual',
									'away',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'manual',
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
						],
					],
				],
			],
			$this->stored($this->thermostat('vt-pin')),
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
	public function testEditThermostat(): void
	{
		$this->install([
			'1', // edit a thermostat
			'', // the only thermostat
			'Renamed thermostat', // name
			'0,1', // heating and cooling
			'1', // fahrenheit
			'80', // maximum floor temperature
			'0,3', // presets: away, comfort
			'73.5', // comfort, the only new one
			'5', // nothing
		]);

		// the installer starts the edited thermostat off, in the manual preset, and sets the new preset's temperature
		self::assertSame(
			[
				[
					'state',
					'hvac_mode',
					[
						'actual_value' => 'off',
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'state',
					'preset_mode',
					[
						'actual_value' => 'manual',
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
				[
					'preset_comfort',
					'target_room_temperature',
					[
						'actual_value' => 73.5,
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
			],
			$this->set,
		);

		self::assertSame(
			[
				'type' => 'virtual-thermostat-addon',
				'identifier' => 'thermostat-office',
				'name' => 'Renamed thermostat',
				'connector' => 'virtual',
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
						'value' => 'generated',
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
						'value' => 'virtual-thermostat-addon',
						'children' => [],
					],
					'state' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => null,
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
				],
				'channels' => [
					'actors' => [
						'type' => 'virtual-thermostat-addon-actors',
						'name' => null,
						'properties' => [
							'heater_actor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'heater_actor_1',
								'name' => null,
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '11807caa-082c-468b-8a1b-88ba8a715ca1',
								'settable' => true,
								'queryable' => true,
							],
						],
					],
					'configuration' => [
						'type' => 'virtual-thermostat-addon-configuration',
						'name' => null,
						'properties' => [
							'high_target_temperature_tolerance' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'high_target_temperature_tolerance',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'value' => 0.3,
								'children' => [],
							],
							'low_target_temperature_tolerance' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'low_target_temperature_tolerance',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'value' => 0.3,
								'children' => [],
							],
							'max_floor_temperature' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'max_floor_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									0.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 30.0,
								'value_transformer' => null,
								'value' => null,
								'children' => [],
							],
						],
					],
					'preset_away' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'preset_comfort' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 20.0,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'preset_manual' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'sensors' => [
						'type' => 'virtual-thermostat-addon-sensors',
						'name' => null,
						'properties' => [
							'floor_temperature_sensor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'floor_temperature_sensor_1',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '1e196c5c-a469-4ec7-95e7-c4bb48d58fe0',
								'settable' => false,
								'queryable' => true,
							],
							'room_temperature_sensor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'room_temperature_sensor_1',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '9c5e5a5f-1b5d-4394-a9b9-199f1701efac',
								'settable' => false,
								'queryable' => true,
							],
						],
					],
					'state' => [
						'type' => 'virtual-thermostat-addon-state',
						'name' => null,
						'properties' => [
							'current_floor_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'current_floor_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									0.0,
									1_000.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'current_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'current_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									1_000.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'floor_overheating' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'floor_overheating',
								'name' => null,
								'data_type' => 'bool',
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
							'hvac_mode' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hvac_mode',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'heat',
									'cool',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'off',
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
							'hvac_state' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hvac_state',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'heating',
									'cooling',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'off',
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'preset_mode' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'preset_mode',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'manual',
									'away',
									'comfort',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'manual',
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
						],
					],
				],
			],
			$this->stored($this->thermostat('thermostat-office')),
		);
	}

	/**
	 * Editing a preset needs the thermostat's unit, which the fixture thermostat does not have, so the case edits a
	 * thermostat the installer creates first
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditPreset(): void
	{
		$this->install([
			'0', '', 'vt-pin', 'Pinned thermostat', '0', '0', '0', '0', '0', 'no', 'no', '0', '0', '0', 'no', 'no', 'no',
			'0', '18.5', '21.5', '5',
		]);

		$this->set = [];

		$this->install([
			'3', // manage a thermostat
			'0', // the created one
			'8', // edit a preset
			'0', // the first preset
			'19.5', // its target temperature
			'9', // nothing
			'5', // nothing
		]);

		self::assertSame(
			[
				[
					'preset_manual',
					'target_room_temperature',
					[
						'actual_value' => 19.5,
						'expected_value' => null,
					],
					'com.fastybird.virtual-thermostat-addon',
				],
			],
			$this->set,
		);

		self::assertSame(
			[
				'type' => 'virtual-thermostat-addon',
				'identifier' => 'vt-pin',
				'name' => 'Pinned thermostat',
				'connector' => 'virtual',
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
						'value' => 'generated',
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
						'value' => 'virtual-thermostat-addon',
						'children' => [],
					],
					'state' => [
						'type' => 'dynamic',
						'category' => 'generic',
						'identifier' => 'state',
						'name' => null,
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'connected',
							'disconnected',
							'stopped',
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
				],
				'channels' => [
					'actors' => [
						'type' => 'virtual-thermostat-addon-actors',
						'name' => null,
						'properties' => [
							'heater_actor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'heater_actor_1',
								'name' => null,
								'data_type' => 'bool',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '11807caa-082c-468b-8a1b-88ba8a715ca1',
								'settable' => true,
								'queryable' => true,
							],
						],
					],
					'configuration' => [
						'type' => 'virtual-thermostat-addon-configuration',
						'name' => null,
						'properties' => [
							'high_target_temperature_tolerance' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'high_target_temperature_tolerance',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 0.3,
								'value_transformer' => null,
								'value' => 0.3,
								'children' => [],
							],
							'low_target_temperature_tolerance' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'low_target_temperature_tolerance',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 0.3,
								'value_transformer' => null,
								'value' => 0.3,
								'children' => [],
							],
							'unit' => [
								'type' => 'variable',
								'category' => 'generic',
								'identifier' => 'unit',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'°C',
									'°F',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'value' => '°C',
								'children' => [],
							],
						],
					],
					'preset_away' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 20.0,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'preset_manual' => [
						'type' => 'virtual-thermostat-addon-preset',
						'name' => null,
						'properties' => [
							'target_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'target_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									35.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => 20.0,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
						],
					],
					'sensors' => [
						'type' => 'virtual-thermostat-addon-sensors',
						'name' => null,
						'properties' => [
							'room_temperature_sensor_1' => [
								'type' => 'mapped',
								'category' => 'generic',
								'identifier' => 'room_temperature_sensor_1',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => '1e196c5c-a469-4ec7-95e7-c4bb48d58fe0',
								'settable' => false,
								'queryable' => true,
							],
						],
					],
					'state' => [
						'type' => 'virtual-thermostat-addon-state',
						'name' => null,
						'properties' => [
							'current_room_temperature' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'current_room_temperature',
								'name' => null,
								'data_type' => 'float',
								'unit' => null,
								'format' => [
									7.0,
									1_000.0,
								],
								'invalid' => null,
								'scale' => null,
								'step' => 0.1,
								'default' => null,
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'hvac_mode' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hvac_mode',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'heat',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'off',
								'value_transformer' => null,
								'settable' => true,
								'queryable' => true,
								'children' => [],
							],
							'hvac_state' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hvac_state',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'off',
									'heating',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'off',
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
							'preset_mode' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'preset_mode',
								'name' => null,
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									'manual',
									'away',
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => 'manual',
								'value_transformer' => null,
								'settable' => false,
								'queryable' => true,
								'children' => [],
							],
						],
					],
				],
			],
			$this->stored($this->thermostat('vt-pin')),
		);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(VirtualThermostatEntities\Devices\Device $device): array
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

			// The Virtual connector generates the MAC address at random
			if ($property->getIdentifier() === 'hardware_mac_address') {
				assert(is_string($item['value']));
				self::assertMatchesRegularExpression('/^([0-9a-f]{2}:){6}[0-9a-f]{2}$/', $item['value']);

				$item['value'] = 'generated';
			}

			$data[$property->getIdentifier()] = $item;
		}

		ksort($data);

		return $data;
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function install(array $inputs): string
	{
		$this->keepTheDatabase();
		$this->recordStates();

		$application = new Application();
		$application->add($this->getContainer()->getByType(Commands\Install::class));

		$commandTester = new CommandTester($application->find(Commands\Install::NAME));
		$commandTester->setInputs($inputs);
		$commandTester->execute([]);

		$this->getEntityManager()->clear();

		return $commandTester->getDisplay();
	}

	/**
	 * The state manager is replaced by a recorder of what set() is handed, and has no state to read. A PropertyUpdate is recorded as the array of its fields.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		if ($this->statesRecorded) {
			return;
		}

		$this->statesRecorded = true;

		$this->mockContainerService(
			DevicesModels\States\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\ChannelPropertiesManager::class,
				function (MockObject $mock): void {
					$mock
						->method('set')
						->willReturnCallback(function (
							DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
							DevicesStates\PropertyUpdate $data,
							Sources\Source|null $source,
						): void {
							$this->set[] = [$this->channelIdentifier(
								$property,
							), $property->getIdentifier(), $data->toArray(), $source?->value];
						});
					$mock->method('read')->willReturn(null);
				},
			),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function channelIdentifier(DevicesDocuments\Channels\Properties\Property $property): string
	{
		$channel = $this->getContainer()->getByType(DevicesModels\Entities\Channels\ChannelsRepository::class)->find(
			$property->getChannel(),
		);
		assert($channel !== null);

		return $channel->getIdentifier();
	}

	/**
	 * The installer closes the connection when it is done, and the test connection wrapper answers a reconnect with a
	 * new, empty database. The helper is replaced, once, by one that does everything else as the real one does and,
	 * instead of closing the connection, rolls back a transaction left open and clears the entity manager.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function keepTheDatabase(): void
	{
		if ($this->databaseKept) {
			return;
		}

		$database = $this->getContainer()->getByType(PersistenceHelpers\Database::class);
		$entityManager = $this->getEntityManager();

		$mock = $this->mockOf(
			PersistenceHelpers\Database::class,
			static function (MockObject $mock) use ($database, $entityManager): void {
				$mock
					->method('query')
					->willReturnCallback(static fn (callable $callback): mixed => $database->query($callback));
				$mock
					->method('transaction')
					->willReturnCallback(static fn (callable $callback): mixed => $database->transaction($callback));
				$mock
					->method('beginTransaction')
					->willReturnCallback(static function () use ($database): void {
						$database->beginTransaction();
					});
				$mock
					->method('commitTransaction')
					->willReturnCallback(static function () use ($database): void {
						$database->commitTransaction();
					});
				$mock
					->method('clear')
					->willReturnCallback(static function () use ($entityManager): void {
						// Closing the connection discards a transaction the installer did not commit; so does this
						if ($entityManager->getConnection()->isTransactionActive()) {
							$entityManager->getConnection()->rollBack();
						}

						$entityManager->clear();
					});
			},
		);

		$this->mockContainerService(PersistenceHelpers\Database::class, $mock);

		$this->databaseKept = true;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VirtualThermostatExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function thermostat(string $identifier): VirtualThermostatEntities\Devices\Device
	{
		$findDeviceQuery = new DevicesQueries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier($identifier);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			VirtualThermostatEntities\Devices\Device::class,
		);

		self::assertInstanceOf(VirtualThermostatEntities\Devices\Device::class, $device);

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
