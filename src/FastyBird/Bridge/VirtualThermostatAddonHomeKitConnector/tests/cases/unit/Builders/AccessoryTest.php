<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\Builders;

use Error;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities as VirtualThermostatAddonHomeKitConnectorEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions as VirtualThermostatAddonHomeKitConnectorExceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries as VirtualThermostatAddonHomeKitConnectorQueries;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\DI;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;
use Throwable;
use function array_map;
use function assert;
use function is_array;
use function ksort;

/**
 * What the builder stores for an accessory, field by field: the accessory device with its parent and its properties,
 * and its thermostat service with its characteristics. The fixture has the accessory already, so building updates it;
 * building after it is deleted creates it, and both store the same.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccessoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidArgument
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 * @throws Throwable
	 */
	public function testBuildAndRebuild(): void
	{
		$builder = $this->getContainer()->getByType(Builders\Builder::class);

		$builder->build($this->thermostat(), $this->connector());

		$this->getEntityManager()->clear();

		$built = $this->stored($this->accessory());

		self::assertSame($this->expected(), $built);

		$builder->build($this->thermostat(), $this->connector(), $this->accessory());

		$this->getEntityManager()->clear();

		self::assertSame($built, $this->stored($this->accessory()));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws DI\MissingServiceException
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidArgument
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 * @throws Throwable
	 */
	public function testBuildNew(): void
	{
		$this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesManager::class)->delete(
			$this->accessory(),
		);

		$this->getEntityManager()->clear();

		$this->getContainer()->getByType(Builders\Builder::class)->build($this->thermostat(), $this->connector());

		$this->getEntityManager()->clear();

		// a new accessory is a new HomeKit device, which gets HomeKit's connection state property as well, and a new
		// service is named by the translation, where the fixture's has its own name
		$expected = $this->expected();
		assert(is_array($expected['properties']));
		assert(is_array($expected['channels']) && is_array($expected['channels']['thermostat_1']));
		$expected['channels']['thermostat_1']['name'] = 'Thermostat';
		$expected['properties']['state'] = [
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
		];

		self::assertSame($expected, $this->stored($this->accessory()));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function expected(): array
	{
		return [
			'type' => 'virtual-thermostat-addon-homekit-connector-bridge',
			'identifier' => 'thermostat-office',
			'name' => 'Thermostat - Office',
			'connector' => 'homekit',
			'parents' => [
				'thermostat-office',
			],
			'properties' => [
				'category' => [
					'type' => 'variable',
					'category' => 'generic',
					'identifier' => 'category',
					'name' => null,
					'data_type' => 'uchar',
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'default' => null,
					'value_transformer' => null,
					'value' => 9,
					'children' => [],
				],
				'firmware_manufacturer' => [
					'type' => 'variable',
					'category' => 'generic',
					'identifier' => 'firmware_manufacturer',
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
					'value' => 'Virtual Thermostat Bridge',
					'children' => [],
				],
			],
			'channels' => [
				'thermostat_1' => [
					'type' => 'virtual-thermostat-addon-homekit-connector-bridge',
					'name' => 'Thermostat Service',
					'properties' => [
						'current_heating_cooling_state' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'current_heating_cooling_state',
							'name' => null,
							'data_type' => 'enum',
							'unit' => null,
							'format' => [
								[
									'off',
									'0',
									'0',
								],
								[
									'heating',
									'1',
									'1',
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'parent' => '04cd4f8f-e8a6-40a3-b2ef-33f574988ee8',
							'settable' => false,
							'queryable' => true,
						],
						'current_temperature' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'current_temperature',
							'name' => null,
							'data_type' => 'float',
							'unit' => null,
							'format' => [
								7.0,
								35.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'parent' => 'a74d06a4-8eb2-440e-8bb0-6ec54a0bf93c',
							'settable' => false,
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
							'value' => 'Thermostat - Office',
							'children' => [],
						],
						'target_heating_cooling_state' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'target_heating_cooling_state',
							'name' => null,
							'data_type' => 'enum',
							'unit' => null,
							'format' => [
								[
									'off',
									'0',
									'0',
								],
								[
									'heat',
									'1',
									'1',
								],
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'parent' => 'a326ba38-d188-4eac-a6ad-43bdcc84a730',
							'settable' => true,
							'queryable' => true,
						],
						'target_temperature' => [
							'type' => 'mapped',
							'category' => 'generic',
							'identifier' => 'target_temperature',
							'name' => null,
							'data_type' => 'float',
							'unit' => null,
							'format' => [
								7.0,
								35.0,
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => null,
							'value_transformer' => null,
							'parent' => '17627f14-ebbf-4bc1-88fd-e8fc32d3e5de',
							'settable' => true,
							'queryable' => true,
						],
						'temperature_display_units' => [
							'type' => 'dynamic',
							'category' => 'generic',
							'identifier' => 'temperature_display_units',
							'name' => null,
							'data_type' => 'enum',
							'unit' => null,
							'format' => [
								'0',
								'1',
							],
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'default' => '0',
							'value_transformer' => null,
							'settable' => true,
							'queryable' => false,
							'children' => [],
						],
					],
				],
			],
		];
	}

	/**
	 * The accessory as plain arrays, without the generated identifiers and timestamps
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat $accessory): array
	{
		$channels = [];

		foreach ($accessory->getChannels() as $channel) {
			$channels[$channel->getIdentifier()] = [
				'type' => $channel::getType(),
				'name' => $channel->getName(),
				'properties' => $this->properties($channel->getProperties()),
			];
		}

		ksort($channels);

		return [
			'type' => $accessory::getType(),
			'identifier' => $accessory->getIdentifier(),
			'name' => $accessory->getName(),
			'connector' => $accessory->getConnector()->getIdentifier(),
			'parents' => array_map(
				static fn (DevicesEntities\Devices\Device $parent): string => $parent->getIdentifier(),
				$accessory->getParents(),
			),
			'properties' => $this->properties($accessory->getProperties()),
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
	 * @throws DI\MissingServiceException
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidArgument
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function thermostat(): VirtualThermostatEntities\Devices\Device
	{
		$findThermostatQuery = new DevicesQueries\Entities\FindDevices();
		$findThermostatQuery->byIdentifier('thermostat-office');

		$thermostat = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		)->findOneBy($findThermostatQuery);

		self::assertInstanceOf(VirtualThermostatEntities\Devices\Device::class, $thermostat);

		return $thermostat;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidArgument
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function connector(): HomeKitEntities\Connectors\Connector
	{
		$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('homekit');

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery);

		self::assertInstanceOf(HomeKitEntities\Connectors\Connector::class, $connector);

		return $connector;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws VirtualThermostatAddonHomeKitConnectorExceptions\InvalidArgument
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function accessory(): VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat
	{
		$findAccessoryQuery = new VirtualThermostatAddonHomeKitConnectorQueries\Entities\FindThermostatDevices();
		$findAccessoryQuery->forParent($this->thermostat());

		$accessory = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		)->findOneBy(
			$findAccessoryQuery,
			VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat::class,
		);

		self::assertInstanceOf(VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat::class, $accessory);

		return $accessory;
	}

}
