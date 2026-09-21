<?php declare(strict_types = 1);

/**
 * DeviceProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Throwable;
use function assert;
use function count;
use function React\Async\await;
use function sprintf;

/**
 * Device property MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceProperty implements Queue\Consumer
{

	use Nette\SmartObject;
	use TProperty;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ParseMessage
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Throwable
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\DeviceProperty) {
			return false;
		}

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $message->getDevice()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'device-property-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $message->getDevice(),
					],
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::from($message->getProperty()));

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->warning(
				sprintf('Property "%s" is not registered', $message->getProperty()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'device-property-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'identifier' => $message->getProperty(),
					],
				],
			);

			return true;
		}

		if ($message->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
			if ($property instanceof DevicesDocuments\Devices\Properties\Variable) {
				$this->databaseHelper->transaction(function () use ($message, $property): void {
					$property = $this->devicesPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Devices\Properties\Property);

					$this->devicesPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $message->getValue(),
						]),
					);
				});
			} elseif ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				await($this->devicePropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $message->getValue(),
					]),
					MetadataTypes\Sources\Connector::FB_MQTT,
				));
			}
		} else {
			if (count($message->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($message, $property): void {
					$property = $this->devicesPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Devices\Properties\Property);

					$toUpdate = $this->handlePropertyConfiguration($message);

					if ($toUpdate !== []) {
						$this->devicesPropertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
					}
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
				'type' => 'device-property-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
