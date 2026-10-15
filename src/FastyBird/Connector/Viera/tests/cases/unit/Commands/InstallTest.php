<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\Viera\API;
use FastyBird\Connector\Viera\Commands;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions as VieraExceptions;
use FastyBird\Connector\Viera\Queries;
use FastyBird\Connector\Viera\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function assert;

/**
 * What the installer stores for a connector and a device, field by field. The installer catches every error and only
 * prints it, so each case reads the database back instead of trusting the exit code.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class InstallTest extends Tests\Cases\Unit\DbTestCase
{

	private bool $databaseKept = false;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateConnector(): void
	{
		// create a connector, identifier, name, then nothing
		$this->install(['0', 'viera-pin', 'Pinned connector', '5']);

		self::assertSame(
			[
				'type' => 'viera-connector',
				'identifier' => 'viera-pin',
				'name' => 'Pinned connector',
				'comment' => null,
				'enabled' => true,
				'properties' => [],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('viera-pin')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, the only connector, name, disable: yes, then nothing
		$this->install(['1', '', 'Renamed', 'yes', '5']);

		self::assertSame(
			[
				'type' => 'viera-connector',
				'identifier' => 'viera',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => false,
				'properties' => [],
				'controls' => [
					'discover',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('viera')),
		);
	}

	/**
	 * A television is created from what it reports about itself and what the installer is told, then edited: it lost
	 * its channel in between, so the edit creates it again
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateAndEditDevice(): void
	{
		$this->television();

		// manage, the only connector, create a device, address, configure HDMI: yes, name, number, another: yes,
		// name, number, another: no, configure the MAC address: yes, MAC address, nothing, nothing
		$this->install([
			'3',
			'',
			'0',
			'10.10.0.50',
			'yes',
			'Apple TV',
			'1',
			'yes',
			'Game console',
			'3',
			'no',
			'yes',
			'aa:bb:cc:dd:ee:ff',
			'5',
			'5',
		]);

		self::assertSame(
			[
				'type' => 'viera-connector',
				'identifier' => '4D454930-0200-1000-8001-PIN000000001',
				'name' => 'Living room TV',
				'comment' => null,
				'connector' => 'viera',
				'parents' => [],
				'properties' => [
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
						'value' => false,
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
						'value' => 'Panasonic',
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
						'value' => 'TX-55EZ950E TX-55EZ950E-X',
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
						'value' => '10.10.0.50',
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
						'value' => 55_000,
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
						'value' => '4D454930-0200-1000-8001-PIN000000001',
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
				],
				'controls' => [],
				'channels' => [
					'television' => [
						'type' => 'viera-connector',
						'name' => null,
						'comment' => null,
						'properties' => [
							'application' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'application',
								'name' => 'Application',
								'data_type' => 'enum',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => false,
								'children' => [],
							],
							'hdmi' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'hdmi',
								'name' => 'Hdmi',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									[
										'apple_tv',
										'1',
										'1',
									],
									[
										'game_console',
										'3',
										'3',
									],
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
							'input_source' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'input_source',
								'name' => 'Input source',
								'data_type' => 'enum',
								'unit' => null,
								'format' => [
									[
										'TV',
										'500',
										'500',
									],
									[
										'apple_tv',
										'1',
										'1',
									],
									[
										'game_console',
										'3',
										'3',
									],
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
							'key_0' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_0',
								'name' => 'Key 0',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D0-ONOFF',
										'NRC_D0-ONOFF',
									],
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
							'key_1' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_1',
								'name' => 'Key 1',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D1-ONOFF',
										'NRC_D1-ONOFF',
									],
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
							'key_2' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_2',
								'name' => 'Key 2',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D2-ONOFF',
										'NRC_D2-ONOFF',
									],
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
							'key_3' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_3',
								'name' => 'Key 3',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D3-ONOFF',
										'NRC_D3-ONOFF',
									],
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
							'key_4' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_4',
								'name' => 'Key 4',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D4-ONOFF',
										'NRC_D4-ONOFF',
									],
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
							'key_5' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_5',
								'name' => 'Key 5',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D5-ONOFF',
										'NRC_D5-ONOFF',
									],
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
							'key_6' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_6',
								'name' => 'Key 6',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D6-ONOFF',
										'NRC_D6-ONOFF',
									],
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
							'key_7' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_7',
								'name' => 'Key 7',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D7-ONOFF',
										'NRC_D7-ONOFF',
									],
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
							'key_8' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_8',
								'name' => 'Key 8',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D8-ONOFF',
										'NRC_D8-ONOFF',
									],
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
							'key_9' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_9',
								'name' => 'Key 9',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_D9-ONOFF',
										'NRC_D9-ONOFF',
									],
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
							'key_arrow_down' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_arrow_down',
								'name' => 'Key arrow down',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_DOWN-ONOFF',
										'NRC_DOWN-ONOFF',
									],
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
							'key_arrow_left' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_arrow_left',
								'name' => 'Key arrow left',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_LEFT-ONOFF',
										'NRC_LEFT-ONOFF',
									],
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
							'key_arrow_right' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_arrow_right',
								'name' => 'Key arrow right',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_RIGHT-ONOFF',
										'NRC_RIGHT-ONOFF',
									],
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
							'key_arrow_up' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_arrow_up',
								'name' => 'Key arrow up',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_UP-ONOFF',
										'NRC_UP-ONOFF',
									],
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
							'key_back' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_back',
								'name' => 'Key back',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_RETURN-ONOFF',
										'NRC_RETURN-ONOFF',
									],
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
							'key_blue' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_blue',
								'name' => 'Key blue',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_BLUE-ONOFF',
										'NRC_BLUE-ONOFF',
									],
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
							'key_channel_down' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_channel_down',
								'name' => 'Key channel down',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_CH_DOWN-ONOFF',
										'NRC_CH_DOWN-ONOFF',
									],
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
							'key_channel_up' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_channel_up',
								'name' => 'Key channel up',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_CH_UP-ONOFF',
										'NRC_CH_UP-ONOFF',
									],
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
							'key_green' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_green',
								'name' => 'Key green',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_GREEN-ONOFF',
										'NRC_GREEN-ONOFF',
									],
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
							'key_home' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_home',
								'name' => 'Key home',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_HOME-ONOFF',
										'NRC_HOME-ONOFF',
									],
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
							'key_menu' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_menu',
								'name' => 'Key menu',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_MENU-ONOFF',
										'NRC_MENU-ONOFF',
									],
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
							'key_ok' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_ok',
								'name' => 'Key ok',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_ENTER-ONOFF',
										'NRC_ENTER-ONOFF',
									],
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
							'key_red' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_red',
								'name' => 'Key red',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_RED-ONOFF',
										'NRC_RED-ONOFF',
									],
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
							'key_tv' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_tv',
								'name' => 'Key tv',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_TV-ONOFF',
										'NRC_TV-ONOFF',
									],
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
							'key_volume_down' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_volume_down',
								'name' => 'Key volume down',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_VOLDOWN-ONOFF',
										'NRC_VOLDOWN-ONOFF',
									],
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
							'key_volume_up' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_volume_up',
								'name' => 'Key volume up',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_VOLUP-ONOFF',
										'NRC_VOLUP-ONOFF',
									],
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
							'key_yellow' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'key_yellow',
								'name' => 'Key yellow',
								'data_type' => 'button',
								'unit' => null,
								'format' => [
									[
										'btn_clicked',
										'NRC_YELLOW-ONOFF',
										'NRC_YELLOW-ONOFF',
									],
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
							'mute' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'mute',
								'name' => 'Mute',
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
							'remote' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'remote',
								'name' => 'Remote',
								'data_type' => 'string',
								'unit' => null,
								'format' => null,
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'settable' => true,
								'queryable' => false,
								'children' => [],
							],
							'state' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'state',
								'name' => 'State',
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
							'volume' => [
								'type' => 'dynamic',
								'category' => 'generic',
								'identifier' => 'volume',
								'name' => 'Volume',
								'data_type' => 'uchar',
								'unit' => null,
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

		$channelsManager = $this->getContainer()->getByType(DevicesModels\Entities\Channels\ChannelsManager::class);

		foreach ($this->device()->getChannels() as $channel) {
			$channelsManager->delete($channel);
		}

		$this->getEntityManager()->clear();

		// manage, the only connector, edit a device, the only device, name, change the address: no, the port: no,
		// configure HDMI: yes, name, number, another: no, change the MAC address: yes, MAC address, nothing, nothing
		$this->install([
			'3',
			'',
			'1',
			'',
			'Renamed TV',
			'no',
			'no',
			'yes',
			'Blu-ray',
			'2',
			'no',
			'yes',
			'11:22:33:44:55:66',
			'5',
			'5',
		]);

		self::assertSame(
			[
				'type' => 'viera-connector',
				'identifier' => '4D454930-0200-1000-8001-PIN000000001',
				'name' => 'Living room TV',
				'comment' => null,
				'connector' => 'viera',
				'parents' => [],
				'properties' => [
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
						'value' => false,
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
						'value' => 'Panasonic',
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
						'value' => 'TX-55EZ950E TX-55EZ950E-X',
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
						'value' => '10.10.0.50',
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
						'value' => 55_000,
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
						'value' => '4D454930-0200-1000-8001-PIN000000001',
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
				],
				'controls' => [],
				'channels' => [],
			],
			Tests\Support\StoredEntities::device($this->device()),
		);
	}

	/**
	 * The television the installer talks to: online, switched off, and without encryption
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function television(): void
	{
		$television = $this->mockOf(
			API\TelevisionApi::class,
			static function (MockObject $mock): void {
				$mock->method('livenessProbe')->willReturn(true);
				$mock->method('isTurnedOn')->willReturn(false);
				$mock->method('getSpecs')->willReturn(new API\Messages\Response\DeviceSpecs(
					'urn:panasonic-com:device:p00RemoteController:1',
					'Living room TV',
					'Panasonic',
					'TX-55EZ950E',
					'TX-55EZ950E-X',
					'4D454930-0200-1000-8001-PIN000000001',
				));
			},
		);

		$factory = $this->createMock(API\TelevisionApiFactory::class);
		$factory->method('create')->willReturn($television);

		$this->mockContainerService(API\TelevisionApiFactory::class, $factory);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('4D454930-0200-1000-8001-PIN000000001');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);

		return $device;
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function install(array $inputs): void
	{
		$this->keepTheDatabase();

		$application = new Application();
		$application->add($this->getContainer()->getByType(Commands\Install::class));

		$commandTester = new CommandTester($application->find(Commands\Install::NAME));
		$commandTester->setInputs($inputs);
		$commandTester->execute([]);

		$this->getEntityManager()->clear();
	}

	/**
	 * The installer closes the connection when it is done, and the test connection wrapper answers a reconnect with a
	 * new, empty database. The helper is replaced, once, by one that does everything else as the real one does and, instead
	 * of closing the connection, rolls back a transaction left open and clears the entity manager.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
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
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function connector(string $identifier): Entities\Connectors\Connector
	{
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier($identifier);

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);

		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		return $connector;
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
