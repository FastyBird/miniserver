<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions as FbMqttExceptions;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Models as DevicesModels;
use Override;
use function sprintf;

/**
 * Device extension MQTT message consumer
 */
final readonly class ExtensionAttribute implements Queue\Consumer
{

	public function __construct(
		private FbMqtt\Logger $logger,
		private DevicesModels\Entities\Devices\DevicesRepository $deviceRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesRepository $propertiesRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesManager $propertiesManager,
		private Helpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws FbMqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 */
	#[Override]
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\ExtensionAttribute) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->deviceRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $message->getDevice()),
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'extension-attribute-message-consumer',
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

		$propertyIdentifier = null;

		// HARDWARE INFO
		if (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_HARDWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_MANUFACTURER;

		} elseif (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_HARDWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MODEL
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_MODEL;

		} elseif (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_HARDWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::VERSION
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_VERSION;

		} elseif (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_HARDWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MAC_ADDRESS
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;

			// FIRMWARE INFO
		} elseif (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_FIRMWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::MANUFACTURER
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER;

		} elseif (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_FIRMWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::NAME
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::FIRMWARE_NAME;

		} elseif (
			$message->getExtension() === Types\ExtensionType::FASTYBIRD_FIRMWARE
			&& $message->getParameter() === Queue\Messages\ExtensionAttribute::VERSION
		) {
			$propertyIdentifier = Types\DevicePropertyIdentifier::FIRMWARE_VERSION;
		}

		if ($propertyIdentifier === null) {
			return true;
		}

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier($propertyIdentifier);

		$property = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($property === null) {
			$this->logger->warning(
				sprintf('Device property "%s" is not registered', $message->getParameter()),
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'extension-attribute-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'identifier' => $message->getParameter(),
					],
				],
			);

			return true;
		}

		$this->databaseHelper->transaction(function () use ($message, $property): void {
			$toUpdate = [
				'value' => $message->getValue(),
			];

			$this->propertiesManager->update($property, $toUpdate);
		});

		$this->logger->debug(
			'Consumed extension property message',
			[
				'source' => Sources\Connector::FB_MQTT->value,
				'type' => 'extension-attribute-message-consumer',
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
