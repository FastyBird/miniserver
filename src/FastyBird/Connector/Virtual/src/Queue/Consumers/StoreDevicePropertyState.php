<?php declare(strict_types = 1);

/**
 * StoreDevicePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use TypeError;
use ValueError;
use function array_merge;
use function assert;
use function is_string;
use function React\Async\await;

/**
 * Store device property state message consumer
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 * @throws InvalidArgumentException
	 */
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
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => $message->getSource()->value,
					'type' => 'store-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'property' => array_merge(
						is_string($message->getProperty()) ? ['identifier' => $message->getProperty()] : [],
						!is_string($message->getProperty()) ? ['id' => $message->getProperty()->toString()] : [],
					),
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		if (is_string($message->getProperty())) {
			$findDevicePropertyQuery->byIdentifier($message->getProperty());
		} else {
			$findDevicePropertyQuery->byId($message->getProperty());
		}

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device device property could not be loaded',
				[
					'source' => $message->getSource()->value,
					'type' => 'store-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => array_merge(
						is_string($message->getProperty()) ? ['identifier' => $message->getProperty()] : [],
						!is_string($message->getProperty()) ? ['id' => $message->getProperty()->toString()] : [],
					),
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
				$message->getSource(),
			));
		} elseif ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
			$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
			$findDevicePropertyQuery->byId($property->getParent());

			$parent = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

			if ($parent instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				await($this->devicePropertiesStatesManager->write(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $message->getValue(),
					]),
					$message->getSource(),
				));
			} elseif ($parent instanceof DevicesDocuments\Devices\Properties\Variable) {
				$this->databaseHelper->transaction(function () use ($message, $device, $property, $parent): void {
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
								'source' => $message->getSource()->value,
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
				'source' => $message->getSource()->value,
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
