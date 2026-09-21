<?php declare(strict_types = 1);

/**
 * StoreLocalDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function array_merge;
use function assert;
use function count;

/**
 * Store locally found device details message consumer
 *
 * @package        FastyBird:TuyaConnector!
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
		protected readonly Tuya\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		protected readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		protected readonly ToolsHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreLocalDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getId());

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
					$parents = [];

					if ($message->getGateway() !== null) {
						$findParentDeviceQuery = new Queries\Entities\FindDevices();
						$findParentDeviceQuery->byConnectorId($message->getConnector());
						$findParentDeviceQuery->byIdentifier($message->getGateway());

						$parent = $this->devicesRepository->findOneBy(
							$findParentDeviceQuery,
							Entities\Devices\Device::class,
						);

						if ($parent === null) {
							throw new Exceptions\InvalidState(
								'Parent device could not be loaded for child device',
							);
						}

						$parents = [$parent];
					}

					$device = $this->devicesManager->create(
						Utils\ArrayHash::from(array_merge(
							[
								'entity' => Entities\Devices\Device::class,
								'connector' => $connector,
								'identifier' => $message->getId(),
								'name' => $message->getName(),
							],
							$message->getGateway() !== null
								? ['parents' => $parents]
								: [],
						)),
					);
					assert($device instanceof Entities\Devices\Device);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was created',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'store-local-device-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $message->getId(),
						'address' => $message->getIpAddress(),
					],
					'data' => $message->toArray(),
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$message->getIpAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getVersion(),
			MetadataTypes\DataType::ENUM,
			Types\DevicePropertyIdentifier::PROTOCOL_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PROTOCOL_VERSION->value),
			[
				Types\DeviceProtocolVersion::V31->value,
				Types\DeviceProtocolVersion::V32->value,
				Types\DeviceProtocolVersion::V33->value,
				Types\DeviceProtocolVersion::V34->value,
				Types\DeviceProtocolVersion::V32_PLUS->value,
			],
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getLocalKey(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::LOCAL_KEY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::LOCAL_KEY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getNodeId(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::NODE_ID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::NODE_ID->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getGateway(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::GATEWAY_ID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::GATEWAY_ID->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getCategory(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::CATEGORY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::CATEGORY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getIcon(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::ICON,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ICON->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getLatitude(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::LATITUDE,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::LATITUDE->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getLongitude(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::LONGITUDE,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::LONGITUDE->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getProductId(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::PRODUCT_ID,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PRODUCT_ID->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getProductName(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::PRODUCT_NAME,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PRODUCT_NAME->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->isEncrypted(),
			MetadataTypes\DataType::BOOLEAN,
			Types\DevicePropertyIdentifier::ENCRYPTED,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ENCRYPTED->value),
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
			$message->getMac(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getSn(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::SERIAL_NUMBER,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::SERIAL_NUMBER->value),
		);

		if (count($message->getDataPoints()) > 0) {
			$this->databaseHelper->transaction(function () use ($message, $device): bool {
				$findChannelQuery = new Queries\Entities\FindChannels();
				$findChannelQuery->byIdentifier(Types\DataPoint::LOCAL);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Channels\Channel::class,
						'device' => $device,
						'identifier' => Types\DataPoint::LOCAL->value,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'store-local-device-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				foreach ($message->getDataPoints() as $dataPoint) {
					$this->setChannelProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						$channel->getId(),
						null,
						$dataPoint->getDataType(),
						$dataPoint->getCode(),
						$dataPoint->getCode(),
						$dataPoint->getFormat(),
						$dataPoint->getUnit(),
						null,
						null,
						$dataPoint->getStep(),
						$dataPoint->isSettable(),
						$dataPoint->isQueryable(),
					);
				}

				return true;
			});
		}

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\Sources\Connector::TUYA->value,
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
