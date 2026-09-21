<?php declare(strict_types = 1);

/**
 * DeviceAttribute.php
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
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function assert;
use function in_array;
use function is_array;

/**
 * Device attributes MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceAttribute implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicePropertiesManager,
		private readonly DevicesModels\Entities\Devices\Controls\ControlsRepository $deviceControlsRepository,
		private readonly DevicesModels\Entities\Devices\Controls\ControlsManager $deviceControlsManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\DeviceAttribute) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

		if ($message->getAttribute() === Queue\Messages\Attribute::STATE) {
			assert(!is_array($message->getValue()));

			if (DevicesTypes\ConnectionState::tryFrom($message->getValue()) !== null) {
				if ($device === null) {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\Device::class,
						'identifier' => $message->getDevice(),
					]));
				}

				$this->deviceConnectionManager->setState(
					$device,
					DevicesTypes\ConnectionState::from($message->getValue()),
				);
			}
		} else {
			$this->databaseHelper->transaction(function () use ($message, $device): void {
				$toUpdate = [
					'entity' => Entities\Devices\Device::class,
				];

				if ($message->getAttribute() === Queue\Messages\Attribute::NAME) {
					$toUpdate['name'] = $message->getValue();
				}

				if ($device === null) {
					$toUpdate['identifier'] = $message->getDevice();

					$device = $this->devicesManager->create(Utils\ArrayHash::from($toUpdate));

				} elseif ($toUpdate !== []) {
					$this->devicesManager->update($device, Utils\ArrayHash::from($toUpdate));
				}

				if (
					$message->getAttribute() === Queue\Messages\Attribute::PROPERTIES
					&& is_array($message->getValue())
				) {
					$this->setDeviceProperties($device, Utils\ArrayHash::from($message->getValue()));
				}

				if (
					$message->getAttribute() === Queue\Messages\Attribute::EXTENSIONS
					&& is_array($message->getValue())
				) {
					$this->setDeviceExtensions($device, Utils\ArrayHash::from($message->getValue()));
				}

				if (
					$message->getAttribute() === Queue\Messages\Attribute::CHANNELS
					&& is_array($message->getValue())
				) {
					$this->setDeviceChannels($device, Utils\ArrayHash::from($message->getValue()));
				}

				if (
					$message->getAttribute() === Queue\Messages\Attribute::CONTROLS
					&& is_array($message->getValue())
				) {
					$this->setDeviceControls($device, Utils\ArrayHash::from($message->getValue()));
				}
			});
		}

		$this->logger->debug(
			'Consumed device attribute message',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
				'type' => 'device-attribute-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'identifier' => $message->getDevice(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param Utils\ArrayHash<string> $properties
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function setDeviceProperties(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $properties,
	): void
	{
		foreach ($properties as $propertyName) {
			if ($propertyName === Types\DevicePropertyIdentifier::STATE->value) {
				$this->deviceConnectionManager->setState(
					$device,
					DevicesTypes\ConnectionState::UNKNOWN,
				);
			} else {
				$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::from($propertyName));

				if ($this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery) === null) {
					if (in_array($propertyName, [
						Types\DevicePropertyIdentifier::IP_ADDRESS->value,
						Types\DevicePropertyIdentifier::STATUS_LED->value,
					], true)) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
							'device' => $device,
							'identifier' => $propertyName,
							'name' => $propertyName,
							'settable' => false,
							'queryable' => false,
							'dataType' => MetadataTypes\DataType::STRING,
						]));

					} elseif (in_array($propertyName, [
						Types\DevicePropertyIdentifier::UPTIME->value,
						Types\DevicePropertyIdentifier::FREE_HEAP->value,
						Types\DevicePropertyIdentifier::CPU_LOAD->value,
						Types\DevicePropertyIdentifier::VCC->value,
						Types\DevicePropertyIdentifier::RSSI->value,
					], true)) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
							'device' => $device,
							'identifier' => $propertyName,
							'name' => $propertyName,
							'settable' => false,
							'queryable' => false,
							'dataType' => MetadataTypes\DataType::UINT,
						]));

					} else {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
							'device' => $device,
							'identifier' => $propertyName,
							'settable' => false,
							'queryable' => false,
							'dataType' => MetadataTypes\DataType::UNKNOWN,
						]));
					}
				}
			}
		}

		$findDevicePropertiesQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertiesQuery->forDevice($device);

		// Cleanup for unused properties
		foreach ($this->devicePropertiesRepository->findAllBy($findDevicePropertiesQuery) as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->devicePropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $extensions
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function setDeviceExtensions(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $extensions,
	): void
	{
		foreach ($extensions as $extensionName) {
			if ($extensionName === Types\ExtensionType::FASTYBIRD_HARDWARE->value) {
				foreach ([
					Types\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS,
					Types\DevicePropertyIdentifier::HARDWARE_MANUFACTURER,
					Types\DevicePropertyIdentifier::HARDWARE_MODEL,
					Types\DevicePropertyIdentifier::HARDWARE_VERSION,
				] as $propertyName) {
					$findPropertyQuery = new Queries\Entities\FindDeviceProperties();
					$findPropertyQuery->forDevice($device);
					$findPropertyQuery->byIdentifier($propertyName);

					if ($this->devicePropertiesRepository->findOneBy($findPropertyQuery) === null) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'device' => $device,
							'identifier' => $propertyName->value,
							'dataType' => MetadataTypes\DataType::STRING,
						]));
					}
				}
			} elseif ($extensionName === Types\ExtensionType::FASTYBIRD_FIRMWARE->value) {
				foreach ([
					Types\DevicePropertyIdentifier::FIRMWARE_MANUFACTURER,
					Types\DevicePropertyIdentifier::FIRMWARE_NAME,
					Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
				] as $propertyName) {
					$findPropertyQuery = new Queries\Entities\FindDeviceProperties();
					$findPropertyQuery->forDevice($device);
					$findPropertyQuery->byIdentifier($propertyName);

					if ($this->devicePropertiesRepository->findOneBy($findPropertyQuery) === null) {
						$this->devicePropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Variable::class,
							'device' => $device,
							'identifier' => $propertyName->value,
							'dataType' => MetadataTypes\DataType::STRING,
						]));
					}
				}
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $controls
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function setDeviceControls(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			$findDeviceControlQuery = new DevicesQueries\Entities\FindDeviceControls();
			$findDeviceControlQuery->forDevice($device);
			$findDeviceControlQuery->byName($controlName);

			$control = $this->deviceControlsRepository->findOneBy($findDeviceControlQuery);

			if ($control === null) {
				$this->deviceControlsManager->create(Utils\ArrayHash::from([
					'device' => $device,
					'name' => $controlName,
				]));
			}
		}

		$findDeviceControlsQuery = new DevicesQueries\Entities\FindDeviceControls();
		$findDeviceControlsQuery->forDevice($device);

		// Cleanup for unused control
		foreach ($this->deviceControlsRepository->findAllBy($findDeviceControlsQuery) as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->deviceControlsManager->delete($control);
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $channels
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function setDeviceChannels(
		DevicesEntities\Devices\Device $device,
		Utils\ArrayHash $channels,
	): void
	{
		foreach ($channels as $channelName) {
			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($channelName);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

			if ($channel === null) {
				$this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Channel::class,
					'device' => $device,
					'identifier' => $channelName,
				]));
			}
		}

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		// Cleanup for unused channels
		foreach ($this->channelsRepository->findAllBy(
			$findChannelsQuery,
			Entities\Channels\Channel::class,
		) as $channel) {
			if (!in_array($channel->getIdentifier(), (array) $channels, true)) {
				$this->channelsManager->delete($channel);
			}
		}
	}

}
