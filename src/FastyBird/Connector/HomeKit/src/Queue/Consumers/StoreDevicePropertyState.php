<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Override;
use TypeError;
use ValueError;
use function assert;
use function React\Async\await;

/**
 * Store device property state message consumer
 */
final readonly class StoreDevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private HomeKit\Logger $logger,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private Helpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDevicePropertyState) {
			return false;
		}

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			HomeKitDocuments\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'store-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byId($message->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device device property could not be loaded',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'store-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if ($property instanceof DevicesDocuments\Devices\Properties\Variable) {
			$this->databaseHelper->transaction(
				function () use ($message, $property): void {
					$property = $this->devicesPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Devices\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Devices\Properties\Variable);

					$this->devicesPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $message->getValue(),
						]),
					);
				},
			);

		} elseif ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
			await($this->devicePropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $message->getValue(),
				]),
				Sources\Connector::HOMEKIT,
			));
			await($this->devicePropertiesStatesManager->setValidState(
				$property,
				true,
				Sources\Connector::HOMEKIT,
			));
		} elseif ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
			$findDevicePropertyQuery = new Queries\Configuration\FindDeviceProperties();
			$findDevicePropertyQuery->byId($property->getParent());

			$parent = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

			if ($parent instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				await($this->devicePropertiesStatesManager->write(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $message->getValue(),
					]),
					Sources\Connector::HOMEKIT,
				));
			} elseif ($parent instanceof DevicesDocuments\Devices\Properties\Variable) {
				$this->databaseHelper->transaction(function () use ($message, $parent, $device, $property): void {
					$toUpdate = $this->devicesPropertiesRepository->find(
						$parent->getId(),
						DevicesEntities\Devices\Properties\Variable::class,
					);

					if ($toUpdate !== null) {
						$this->devicesPropertiesManager->update(
							$toUpdate,
							Utils\ArrayHash::from([
								'value' => $message->getValue(),
							]),
						);
					} else {
						$this->logger->error(
							'Mapped variable property could not be updated',
							[
								'source' => Sources\Connector::HOMEKIT->value,
								'type' => 'store-device-property-state-message-consumer',
								'connector' => [
									'id' => $message->getConnector()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
								],
								'data' => $message->toArray(),
							],
						);
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'store-device-property-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
