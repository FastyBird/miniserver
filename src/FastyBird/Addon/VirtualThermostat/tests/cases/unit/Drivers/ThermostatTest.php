<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Cases\Unit\Drivers;

use Error;
use FastyBird\Addon\VirtualThermostat\Documents;
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Queries;
use FastyBird\Addon\VirtualThermostat\Tests;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Drivers as VirtualDrivers;
use FastyBird\Connector\Virtual\Queue as VirtualQueue;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\DI;
use React\EventLoop;
use RuntimeException;
use function array_key_exists;
use function count;
use function in_array;
use function React\Async\await;

final class ThermostatTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function testConnect(): void
	{
		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Configuration\Devices\Repository::class);

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byIdentifier('thermostat-office');

		$device = $devicesRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);
		self::assertInstanceOf(Documents\Devices\Device::class, $device);

		$driversManager = $this->getContainer()->getByType(VirtualDrivers\DriversManager::class);

		$driver = $driversManager->getDriver($device);

		self::assertFalse($driver->isConnected());

		await($driver->connect());

		self::assertTrue($driver->isConnected());
	}

	/**
	 * @param array<string, int|float|bool|string> $readInitialStates
	 * @param array<mixed> $expectedWriteEntities
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 *
	 * @dataProvider processThermostatData
	 */
	public function testProcess(array $readInitialStates, array $expectedWriteEntities): void
	{
		$channelPropertiesStatesManager = $this->createMock(DevicesModels\States\ChannelPropertiesManager::class);
		$channelPropertiesStatesManager
			->method('read')
			->willReturnCallback(
				static function (
					DevicesDocuments\Channels\Properties\Property $property,
				) use ($readInitialStates): DevicesDocuments\States\Channels\Properties\Property|null {
					if (array_key_exists($property->getId()->toString(), $readInitialStates)) {
						return new DevicesDocuments\States\Channels\Properties\Property(
							$property->getId(),
							$property->getChannel(),
							new DevicesDocuments\States\StateValues(
								$readInitialStates[$property->getId()->toString()],
								null,
							),
							new DevicesDocuments\States\StateValues(
								$readInitialStates[$property->getId()->toString()],
								null,
							),
							false,
							true,
						);
					}

					return null;
				},
			);

		$this->mockContainerService(
			DevicesModels\States\ChannelPropertiesManager::class,
			$channelPropertiesStatesManager,
		);

		$storeChannelPropertyStateConsumer = $this->createMock(VirtualQueue\Consumers\StoreChannelPropertyState::class);
		$storeChannelPropertyStateConsumer
			->expects(self::exactly(count($expectedWriteEntities)))
			->method('consume')
			->with(
				self::callback(
					static function (VirtualQueue\Messages\Message $entity) use ($expectedWriteEntities): bool {
						self::assertTrue(in_array($entity->toArray(), $expectedWriteEntities, true));

						return true;
					},
				),
			);

		$this->mockContainerService(
			VirtualQueue\Consumers\StoreChannelPropertyState::class,
			$storeChannelPropertyStateConsumer,
		);

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Configuration\Devices\Repository::class);

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byIdentifier('thermostat-office');

		$device = $devicesRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);
		self::assertInstanceOf(Documents\Devices\Device::class, $device);

		$driversManager = $this->getContainer()->getByType(VirtualDrivers\DriversManager::class);

		$driver = $driversManager->getDriver($device);

		await($driver->connect());

		$driver->process();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(0.1, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(VirtualQueue\Queue::class);

		self::assertFalse($queue->isEmpty());

		// @phpstan-ignore-next-line
		while (!$queue->isEmpty()) {
			$consumers = $this->getContainer()->getByType(VirtualQueue\Consumers::class);

			$consumers->consume();
		}
	}

	/**
	 * Target temperature range: 21.7 - 22.3
	 *
	 * @return array<string, mixed>
	 */
	public static function processThermostatData(): array
	{
		return [
			'keep_off' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => false, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.3, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 24.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'hvac_state',
						'value' => Types\HvacState::OFF->value,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 22.3,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 24.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
			'keep_on' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 21.7, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 22.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => true,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'hvac_state',
						'value' => Types\HvacState::HEATING->value,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 21.7,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 22.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
			'turn_heat_on' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => false, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 21.6, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 22.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => true,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'hvac_state',
						'value' => Types\HvacState::HEATING->value,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 21.6,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 22.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
			'turn_heat_off' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.3, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 22.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'hvac_state',
						'value' => Types\HvacState::OFF->value,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 22.3,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 22.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
			'keep_on_hysteresis' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.0, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 23.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 22.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 23.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
			'keep_off_hysteresis' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => false, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 22.0, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 23.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 22.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 23.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
			'floor_overheat' => [
				// Read properties initial states
				[
					'bceca543-2de7-44b1-8a33-87e9574b6731' => true, // heater_1
					'd58fe894-0d1c-4bf0-bff5-a190cab20e5c' => 21.6, // target_sensor_1
					'e2b98261-2a05-483d-be7c-ac3afe3888b2' => 31.0, // floor_sensor_1
					'17627f14-ebbf-4bc1-88fd-e8fc32d3e5de' => 22.0, // target_temperature - manual
					'a6eb0f6c-c240-4956-b16d-7ada1f337189' => 17.0, // target_temperature - preset_away
					'767ddcf6-24c5-48b0-baaa-e8c7a90d3dc0' => 20.0, // target_temperature - preset_eco
					'15d157d1-0ec7-42a7-9683-51678de1ce9a' => 22.0, // target_temperature - preset_home
					'a326ba38-d188-4eac-a6ad-43bdcc84a730' => Types\HvacMode::HEAT->value, // hvac_mode
					'f0b8100f-5ddb-4abd-8015-d0dbf9a11aa0' => Types\Preset::MANUAL->value, // preset_mode
				],
				// Expected write entities
				[
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => '9808b386-9ed4-4e58-88f1-b39f5f70ef39',
						'property' => 'bceca543-2de7-44b1-8a33-87e9574b6731',
						'value' => false,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'hvac_state',
						'value' => Types\HvacState::OFF->value,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_room_temperature',
						'value' => 21.6,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'current_floor_temperature',
						'value' => 31.0,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
					[
						'connector' => '2b1ce81f-9933-4d52-afd4-bec3583e6a06',
						'device' => '552cea8a-0e81-41d9-be2f-839b079f315e',
						'channel' => 'b453987e-bbf4-46fc-830f-6448b19d9665',
						'property' => 'floor_overheating',
						'value' => true,
						'source' => MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT->value,
					],
				],
			],
		];
	}

}
