<?php declare(strict_types = 1);

/**
 * StoreLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;
use function in_array;

/**
 * Store locally found device details message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreLocalDevice implements Queue\Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;
	use ChannelProperty;

	public function __construct(
		protected readonly Shelly\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly ToolsHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreLocalDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

		if ($device === null) {
			$connector = $this->connectorsRepository->find(
				$message->getConnector(),
				Entities\Connectors\Connector::class,
			);

			if ($connector === null) {
				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($message, $connector): Entities\Devices\Device {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\Device::class,
						'connector' => $connector,
						'identifier' => $message->getIdentifier(),
					]));
					assert($device instanceof Entities\Devices\Device);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was created',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY->value,
					'type' => 'store-local-device-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $message->getIdentifier(),
						'address' => $message->getIpAddress(),
					],
					'data' => $message->toArray(),
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$message->getSerialNumber(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::SERIAL_NUMBER,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::SERIAL_NUMBER->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getIpAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getDomain(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::DOMAIN,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::DOMAIN->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getGeneration()->value,
			MetadataTypes\DataType::ENUM,
			Types\DevicePropertyIdentifier::GENERATION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::GENERATION->value),
			[Types\DeviceGeneration::GENERATION_1->value, Types\DeviceGeneration::GENERATION_2->value],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->isAuthEnabled(),
			MetadataTypes\DataType::BOOLEAN,
			Types\DevicePropertyIdentifier::AUTH_ENABLED,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::AUTH_ENABLED->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getModel(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getMacAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getFirmwareVersion(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value),
		);

		foreach ($message->getChannels() as $channelDescription) {
			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($channelDescription->getIdentifier());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

			$channel = $channel === null ? $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Channel => $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Channel::class,
					'device' => $device,
					'identifier' => $channelDescription->getIdentifier(),
					'name' => $channelDescription->getName(),
				])),
			) : $this->databaseHelper->transaction(
				fn (): DevicesEntities\Channels\Channel => $this->channelsManager->update(
					$channel,
					Utils\ArrayHash::from([
						'name' => $channelDescription->getName(),
					]),
				),
			);

			$propertiesIdentifiers = [];

			foreach ($channelDescription->getProperties() as $propertyDescription) {
				$this->setChannelProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					$channel->getId(),
					null,
					$propertyDescription->getDataType(),
					$propertyDescription->getIdentifier(),
					$propertyDescription->getName(),
					$propertyDescription->getFormat(),
					$propertyDescription->getUnit(),
					$propertyDescription->getInvalid(),
					$propertyDescription->isSettable(),
					$propertyDescription->isQueryable(),
				);

				$propertiesIdentifiers[] = $propertyDescription->getIdentifier();
			}

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery);

			foreach ($properties as $property) {
				if (!in_array($property->getIdentifier(), $propertiesIdentifiers, true)) {
					$this->channelsPropertiesManager->delete($property);
				}
			}
		}

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\Sources\Connector::SHELLY->value,
				'type' => 'store-local-device-message-consumer',
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
