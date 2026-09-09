<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Cases\Unit\Builders;

use Error;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\DI;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class BuilderTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @param array<mixed> $expectedChannels
	 * @param array<mixed> $expectedChannelsProperties
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 *
	 * @dataProvider builder
	 * @throws \Ramsey\Uuid\Exception\InvalidArgumentException
	 */
	public function testBuild(
		string $shellyIdentifier,
		HomeKitTypes\AccessoryCategory $category,
		int $expectedChannelsCnt,
		array $expectedChannels,
		array $expectedChannelsProperties,
	): void
	{
		$builder = $this->getContainer()->getByType(Builders\Builder::class);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class);

		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('homekit');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery);

		self::assertInstanceOf(HomeKitEntities\Connectors\Connector::class, $connector);

		$findShellyQuery = new DevicesQueries\Entities\FindDevices();
		$findShellyQuery->byIdentifier($shellyIdentifier);

		$shelly = $devicesRepository->findOneBy($findShellyQuery);

		self::assertInstanceOf(ShellyEntities\Devices\Device::class, $shelly);

		$bridge = $builder->build($shelly, $connector, $category);

		self::assertSame($shelly, $bridge->getParent());

		self::assertCount($expectedChannelsCnt, $bridge->getChannels());
		self::assertInstanceOf(Entities\Channels\Shelly::class, $bridge->getChannels()[0]);

		$actual = [];

		foreach ($bridge->getChannels() as $channel) {
			$channelData = $channel->toArray();

			unset($channelData['id']);
			unset($channelData['properties']);
			unset($channelData['controls']);
			unset($channelData['device']);

			$actual[$channel->getIdentifier()] = $channelData;
		}

		self::assertSame($expectedChannels, $actual);

		$actual = [];

		foreach ($bridge->getChannels() as $channel) {
			$actual[$channel->getIdentifier()] = [];

			foreach ($channel->getProperties() as $property) {
				$propertyData = $property->toArray();

				unset($propertyData['id']);
				unset($propertyData['channel']);

				$actual[$channel->getIdentifier()][$property->getIdentifier()] = $propertyData;
			}
		}

		self::assertSame($expectedChannelsProperties, $actual);
	}

	/**
	 * @return array<string, array<string|int|array<mixed>|null>>
	 */
	public static function builder(): array
	{
		return [
			'Shelly 1 - Switch' => [
				'98cdac1eb419-shelly1',
				HomeKitTypes\AccessoryCategory::SWITCH,
				1,
				[
					'switch_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-relay',
						'category' => 'generic',
						'identifier' => 'switch_1',
						'name' => 'Switch 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2024-08-20T11:00:00+02:00',
						'updated_at' => '2024-08-20T11:00:00+02:00',
					],
				],
				[
					'switch_1' => [
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+02:00',
							'updated_at' => '2020-04-01T12:00:00+02:00',
							'owner' => null,
							'parent' => 'a4b47b32-97ea-40d2-895c-d642dd3341cc',
							'settable' => true,
							'queryable' => true,
						],
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+02:00',
							'updated_at' => '2020-04-01T12:00:00+02:00',
							'value' => 'Shelly 1',
							'owner' => null,
							'children' => [],
						],
					],
				],
			],
			'Shelly 1 - Outlet' => [
				'98cdac1eb419-shelly1',
				HomeKitTypes\AccessoryCategory::OUTLET,
				1,
				[
					'outlet_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-outlet',
						'category' => 'generic',
						'identifier' => 'outlet_1',
						'name' => 'Outlet 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'outlet_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly 1',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'a4b47b32-97ea-40d2-895c-d642dd3341cc',
							'settable' => true,
							'queryable' => true,
						],
						'outlet_in_use' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'outlet_in_use',
							'name' => null,
							'data_type' => 'bool',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => false,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => false,
							'owner' => null,
							'children' => [],
						],
					],
				],
			],
			'Shelly PM - Outlet' => [
				'98cdac1eb219-shelly1pm',
				HomeKitTypes\AccessoryCategory::OUTLET,
				1,
				[
					'outlet_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-outlet',
						'category' => 'generic',
						'identifier' => 'outlet_1',
						'name' => 'Outlet 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'outlet_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly 1PM',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'cf35ecc0-3df1-45f2-be9e-4d817f69d9a2',
							'settable' => true,
							'queryable' => true,
						],
						'outlet_in_use' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'outlet_in_use',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 3_500.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '708bad7a-d098-47fc-b12e-6f0f3de58b8c',
							'settable' => false,
							'queryable' => true,
						],
					],
				],
			],
			'Shelly RGBW2 - Color' => [
				'e48652-shellyrgbw2',
				HomeKitTypes\AccessoryCategory::LIGHT_BULB,
				1,
				[
					'lightbulb_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-lightbulb',
						'category' => 'generic',
						'identifier' => 'lightbulb_1',
						'name' => 'Lightbulb 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'lightbulb_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly RGBW2 Color',
							'owner' => null,
							'children' => [],
						],
						'color_red' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'color_red',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 255.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '5c23f132-ff1e-4e58-8436-efd05b893ef2',
							'settable' => true,
							'queryable' => true,
						],
						'color_green' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'color_green',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 255.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '36f81543-c4a2-4127-84bc-e7a108820bdc',
							'settable' => true,
							'queryable' => true,
						],
						'color_blue' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'color_blue',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 255.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'b69ea5f0-7774-4f3a-8f06-cb91f75d8b25',
							'settable' => true,
							'queryable' => true,
						],
						'color_white' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'color_white',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 255.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'ae2cec70-cbd5-425a-84d5-bc8c61dc73d0',
							'settable' => true,
							'queryable' => true,
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'c8a8705a-0368-4e85-81e2-bf4051c8d6d7',
							'settable' => true,
							'queryable' => true,
						],
						'hue' => [
							'type' => 'dynamic',
							'category' => 'generic',
							'identifier' => 'hue',
							'name' => null,
							'data_type' => 'ushort',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 360.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => 0,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'settable' => true,
							'queryable' => false,
							'owner' => null,
							'children' => [],
						],
						'saturation' => [
							'type' => 'dynamic',
							'category' => 'generic',
							'identifier' => 'saturation',
							'name' => null,
							'data_type' => 'float',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => 0.0,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'settable' => true,
							'queryable' => false,
							'owner' => null,
							'children' => [],
						],
						'brightness' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'brightness',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'c3ea1254-a71b-46ba-9bd1-dde826615d34',
							'settable' => true,
							'queryable' => true,
						],
					],
				],
			],
			'Shelly RGBW2 - White' => [
				'c45bbee4c926-shellyrgbw2',
				HomeKitTypes\AccessoryCategory::LIGHT_BULB,
				4,
				[
					'lightbulb_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-lightbulb',
						'category' => 'generic',
						'identifier' => 'lightbulb_1',
						'name' => 'Lightbulb 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					'lightbulb_2' => [
						'type' => 'shelly-connector-homekit-connector-bridge-lightbulb',
						'category' => 'generic',
						'identifier' => 'lightbulb_2',
						'name' => 'Lightbulb 2',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					'lightbulb_3' => [
						'type' => 'shelly-connector-homekit-connector-bridge-lightbulb',
						'category' => 'generic',
						'identifier' => 'lightbulb_3',
						'name' => 'Lightbulb 3',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					'lightbulb_4' => [
						'type' => 'shelly-connector-homekit-connector-bridge-lightbulb',
						'category' => 'generic',
						'identifier' => 'lightbulb_4',
						'name' => 'Lightbulb 4',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'lightbulb_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly RGBW2 White',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'fa1e1d21-a454-4b75-b8e6-c792004afe25',
							'settable' => true,
							'queryable' => true,
						],
						'brightness' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'brightness',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '17617d11-8998-4cfa-9767-165c8893752e',
							'settable' => true,
							'queryable' => true,
						],
					],
					'lightbulb_2' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly RGBW2 White',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '8db03a2c-97f5-4f86-bdbe-bc19644548dd',
							'settable' => true,
							'queryable' => true,
						],
						'brightness' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'brightness',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'a794c9ee-f70f-4b4f-9202-2aca1ce8eb29',
							'settable' => true,
							'queryable' => true,
						],
					],
					'lightbulb_3' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly RGBW2 White',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'fa261e12-0af2-4180-becc-68c76b7ac819',
							'settable' => true,
							'queryable' => true,
						],
						'brightness' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'brightness',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '0119e581-57e2-415f-bffe-77c18f3a0773',
							'settable' => true,
							'queryable' => true,
						],
					],
					'lightbulb_4' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly RGBW2 White',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'switch',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'sw',
										1 => 'switch_on',
									],
									1 => [
										0 => 'b',
										1 => true,
									],
									2 => [
										0 => 'b',
										1 => true,
									],
								],
								1 => [
									0 => [
										0 => 'sw',
										1 => 'switch_off',
									],
									1 => [
										0 => 'b',
										1 => false,
									],
									2 => [
										0 => 'b',
										1 => false,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '3834f124-e590-473f-a816-692fa6690a7d',
							'settable' => true,
							'queryable' => true,
						],
						'brightness' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'brightness',
							'name' => null,
							'data_type' => 'int',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '91d2f0bc-85e3-4a9d-a45b-6e9330ffb550',
							'settable' => true,
							'queryable' => true,
						],
					],
				],
			],
			'Shelly plus 1PM - Switch' => [
				'441793953974-shellyplus1pm',
				HomeKitTypes\AccessoryCategory::SWITCH,
				1,
				[
					'switch_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-relay',
						'category' => 'generic',
						'identifier' => 'switch_1',
						'name' => 'Switch 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'switch_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly plus 1PM',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'bool',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '5deae707-84fd-453b-8fe1-54b3638ae297',
							'settable' => true,
							'queryable' => true,
						],
					],
				],
			],
			'Shelly plus 1PM - Outlet' => [
				'441793953974-shellyplus1pm',
				HomeKitTypes\AccessoryCategory::OUTLET,
				1,
				[
					'outlet_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-outlet',
						'category' => 'generic',
						'identifier' => 'outlet_1',
						'name' => 'Outlet 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'outlet_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly plus 1PM',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'bool',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '5deae707-84fd-453b-8fe1-54b3638ae297',
							'settable' => true,
							'queryable' => true,
						],
						'outlet_in_use' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'outlet_in_use',
							'name' => null,
							'data_type' => 'float',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 4_294_967_295.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '6a7ff31b-7459-4ce3-a2ed-229ee871a27e',
							'settable' => false,
							'queryable' => true,
						],
					],
				],
			],
			'Shelly plus 2PM - Switch' => [
				'441793ad07e8-shellyplus2pm',
				HomeKitTypes\AccessoryCategory::OUTLET,
				2,
				[
					'outlet_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-outlet',
						'category' => 'generic',
						'identifier' => 'outlet_1',
						'name' => 'Outlet 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
					'outlet_2' => [
						'type' => 'shelly-connector-homekit-connector-bridge-outlet',
						'category' => 'generic',
						'identifier' => 'outlet_2',
						'name' => 'Outlet 2',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'outlet_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly plus 2PM',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'bool',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '45cf2c4d-97a8-4edf-9202-a8f015bfab90',
							'settable' => true,
							'queryable' => true,
						],
						'outlet_in_use' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'outlet_in_use',
							'name' => null,
							'data_type' => 'float',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 4_294_967_295.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'c738a8a9-f9f5-4503-8d93-fb619499b801',
							'settable' => false,
							'queryable' => true,
						],
					],
					'outlet_2' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly plus 2PM',
							'owner' => null,
							'children' => [],
						],
						'on' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'on',
							'name' => null,
							'data_type' => 'bool',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '4011856a-a359-443d-bf5b-889f57202a35',
							'settable' => true,
							'queryable' => true,
						],
						'outlet_in_use' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'outlet_in_use',
							'name' => null,
							'data_type' => 'float',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 4_294_967_295.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '550e57c4-6ba1-42bc-bfed-60787e37ccf3',
							'settable' => false,
							'queryable' => true,
						],
					],
				],
			],
			'Shelly plus 2PM - Roller' => [
				'441793ad07ab-shellyplus2pm',
				HomeKitTypes\AccessoryCategory::WINDOW_COVERING,
				1,
				[
					'window_covering_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-window-covering',
						'category' => 'generic',
						'identifier' => 'window_covering_1',
						'name' => 'Window covering 1',
						'comment' => null,
						'connector' => '451ab010-f500-4eff-8289-9ed09e56a887',
						'owner' => null,
						'created_at' => '2020-04-01T12:00:00+00:00',
						'updated_at' => '2020-04-01T12:00:00+00:00',
					],
				],
				[
					'window_covering_1' => [
						'name' => [
							'type' => 'variable',
							'category' => 'generic',
							'identifier' => 'name',
							'name' => null,
							'data_type' => 'string',
							'unit' => null,
							'format' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'value' => 'Shelly plus 2PM - Cover',
							'owner' => null,
							'children' => [],
						],
						'current_position' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'current_position',
							'name' => null,
							'data_type' => 'uchar',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '10d203fd-7648-4ae4-a107-a21f06b94d51',
							'settable' => false,
							'queryable' => true,
						],
						'target_position' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'target_position',
							'name' => null,
							'data_type' => 'uchar',
							'unit' => null,
							'format' => [
								0 => 0.0,
								1 => 100.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => 'add5fcc5-eaa7-4789-813f-3ac4bc0e48cf',
							'settable' => true,
							'queryable' => true,
						],
						'position_state' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'position_state',
							'name' => null,
							'data_type' => 'enum',
							'unit' => null,
							'format' => [
								0 => [
									0 => [
										0 => 'cvr',
										1 => 'cover_stopped',
									],
									1 => [
										0 => 'u8',
										1 => 2,
									],
									2 => [
										0 => 'u8',
										1 => 2,
									],
								],
								1 => [
									0 => [
										0 => 'cvr',
										1 => 'cover_opening',
									],
									1 => [
										0 => 'u8',
										1 => 1,
									],
									2 => [
										0 => 'u8',
										1 => 1,
									],
								],
								2 => [
									0 => [
										0 => 'cvr',
										1 => 'cover_closing',
									],
									1 => [
										0 => 'u8',
										1 => 0,
									],
									2 => [
										0 => 'u8',
										1 => 0,
									],
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'created_at' => '2020-04-01T12:00:00+00:00',
							'updated_at' => '2020-04-01T12:00:00+00:00',
							'owner' => null,
							'parent' => '25979909-fce3-470a-8f7c-765122721f48',
							'settable' => false,
							'queryable' => true,
						],
					],
				],
			],
		];
	}

}
