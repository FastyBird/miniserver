<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests\Cases\Unit\Builders;

use Error;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Builders;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities as ShellyConnectorHomeKitConnectorEntities;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions as ShellyConnectorHomeKitConnectorExceptions;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Tests;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Connector\Shelly\Types as ShellyTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\DI;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;
use Throwable;
use function array_map;
use function ksort;

/**
 * What the builder stores for an accessory, field by field: the accessory device with its parent and its properties,
 * and its channels with their properties. Building it again takes the update paths; building it with another category
 * replaces the channels.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccessoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws ShellyConnectorHomeKitConnectorExceptions\InvalidArgument
	 * @throws ShellyConnectorHomeKitConnectorExceptions\InvalidState
	 * @throws Error
	 * @throws RuntimeException
	 * @throws Throwable
	 */
	public function testBuildAndRebuild(): void
	{
		$builder = $this->getContainer()->getByType(Builders\Builder::class);

		$propertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesManager::class,
		);

		// The fixture device has no serial number; the accessory takes the one the device has
		$serialNumber = $propertiesManager->create([
			'entity' => DevicesEntities\Devices\Properties\Variable::class,
			'device' => $this->shelly(),
			'identifier' => ShellyTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value,
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => '98cdac1eb419',
		]);

		$this->getEntityManager()->clear();

		$builder->build($this->shelly(), $this->connector(), HomeKitTypes\AccessoryCategory::SWITCH);

		$this->getEntityManager()->clear();

		self::assertSame(
			[
				'type' => 'shelly-connector-homekit-connector-bridge',
				'identifier' => '98cdac1eb419-shelly1',
				'name' => 'Shelly 1',
				'connector' => 'homekit',
				'parents' => [
					'98cdac1eb419-shelly1',
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
						'value' => 8,
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
						'value' => 'FastyBird & Shelly',
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
						'value' => 'SHSW-1',
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
						'value' => '98cdac1eb419',
						'children' => [],
					],
				],
				'channels' => [
					'switch_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-relay',
						'name' => 'Switch 1',
						'properties' => [
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
								'value' => 'Shelly 1',
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
											'b',
											true,
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
											'b',
											false,
										],
									],
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => 'a4b47b32-97ea-40d2-895c-d642dd3341cc',
								'settable' => true,
								'queryable' => true,
							],
						],
					],
				],
			],
			self::stored($this->accessory()),
		);

		$serialNumber = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesRepository::class,
		)->find($serialNumber->getId());
		self::assertNotNull($serialNumber);

		$propertiesManager->update($serialNumber, ['value' => '98cdac1eb420']);

		$this->getEntityManager()->clear();

		$builder->build(
			$this->shelly(),
			$this->connector(),
			HomeKitTypes\AccessoryCategory::SWITCH,
			$this->accessory(),
		);

		$this->getEntityManager()->clear();

		self::assertSame(
			[
				'type' => 'shelly-connector-homekit-connector-bridge',
				'identifier' => '98cdac1eb419-shelly1',
				'name' => 'Shelly 1',
				'connector' => 'homekit',
				'parents' => [
					'98cdac1eb419-shelly1',
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
						'value' => 8,
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
						'value' => 'FastyBird & Shelly',
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
						'value' => 'SHSW-1',
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
						'value' => '98cdac1eb420',
						'children' => [],
					],
				],
				'channels' => [
					'switch_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-relay',
						'name' => 'Switch 1',
						'properties' => [
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
								'value' => 'Shelly 1',
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
											'b',
											true,
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
											'b',
											false,
										],
									],
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
								'parent' => 'a4b47b32-97ea-40d2-895c-d642dd3341cc',
								'settable' => true,
								'queryable' => true,
							],
						],
					],
				],
			],
			self::stored($this->accessory()),
		);

		$builder->build(
			$this->shelly(),
			$this->connector(),
			HomeKitTypes\AccessoryCategory::OUTLET,
			$this->accessory(),
		);

		$this->getEntityManager()->clear();

		self::assertSame(
			[
				'type' => 'shelly-connector-homekit-connector-bridge',
				'identifier' => '98cdac1eb419-shelly1',
				'name' => 'Shelly 1',
				'connector' => 'homekit',
				'parents' => [
					'98cdac1eb419-shelly1',
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
						'value' => 7,
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
						'value' => 'FastyBird & Shelly',
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
						'value' => 'SHSW-1',
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
						'value' => '98cdac1eb420',
						'children' => [],
					],
				],
				'channels' => [
					'outlet_1' => [
						'type' => 'shelly-connector-homekit-connector-bridge-outlet',
						'name' => 'Outlet 1',
						'properties' => [
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
								'value' => 'Shelly 1',
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
											'b',
											true,
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
											'b',
											false,
										],
									],
								],
								'invalid' => null,
								'scale' => null,
								'step' => null,
								'default' => null,
								'value_transformer' => null,
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
								'value' => false,
								'children' => [],
							],
						],
					],
				],
			],
			self::stored($this->accessory()),
		);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private static function stored(DevicesEntities\Devices\Device $device): array
	{
		$channels = [];

		foreach ($device->getChannels() as $channel) {
			$channels[$channel->getIdentifier()] = [
				'type' => $channel::getType(),
				'name' => $channel->getName(),
				'properties' => self::properties($channel->getProperties()),
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
			'properties' => self::properties($device->getProperties()),
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
	private static function properties(array $properties): array
	{
		$data = [];

		foreach ($properties as $property) {
			$item = $property->toArray();

			unset($item['id'], $item['created_at'], $item['updated_at'], $item['device'], $item['channel']);
			unset($item['owner']);

			$data[$property->getIdentifier()] = $item;
		}

		ksort($data);

		return $data;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws ShellyConnectorHomeKitConnectorExceptions\InvalidArgument
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
	 * @throws ShellyConnectorHomeKitConnectorExceptions\InvalidArgument
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function shelly(): ShellyEntities\Devices\Device
	{
		$findShellyQuery = new DevicesQueries\Entities\FindDevices();
		$findShellyQuery->byIdentifier('98cdac1eb419-shelly1');

		$shelly = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findShellyQuery,
		);

		self::assertInstanceOf(ShellyEntities\Devices\Device::class, $shelly);

		return $shelly;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws ShellyConnectorHomeKitConnectorExceptions\InvalidArgument
	 * @throws Error
	 * @throws RuntimeException
	 */
	private function accessory(): ShellyConnectorHomeKitConnectorEntities\Devices\Shelly
	{
		$findAccessoryQuery = new DevicesQueries\Entities\FindDevices();
		$findAccessoryQuery->forParent($this->shelly());

		$accessory = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		)->findOneBy($findAccessoryQuery);

		self::assertInstanceOf(ShellyConnectorHomeKitConnectorEntities\Devices\Shelly::class, $accessory);

		return $accessory;
	}

}
