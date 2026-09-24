<?php declare(strict_types = 1);

/**
 * StoreCloudDevice.php
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
use FastyBird\Connector\Tuya\Types as TuyaTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Store cloud found device details message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreCloudDevice implements Queue\Consumer
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
		protected readonly Helpers\Database $databaseHelper,
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
	 * @throws ApplicationExceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreCloudDevice) {
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
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\Device::class,
						'connector' => $connector,
						'identifier' => $message->getId(),
						'name' => $message->getName(),
					]));
					assert($device instanceof Entities\Devices\Device);

					return $device;
				},
			);

			$this->logger->debug(
				'Device was created',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'store-cloud-device-message-consumer',
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
			$message->getLocalKey(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::LOCAL_KEY,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::LOCAL_KEY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getIpAddress(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::IP_ADDRESS,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::IP_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getCategory(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::CATEGORY,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::CATEGORY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getIcon(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::ICON,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::ICON->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getLatitude(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::LATITUDE,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::LATITUDE->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getLongitude(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::LONGITUDE,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::LONGITUDE->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getProductId(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::PRODUCT_ID,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::PRODUCT_ID->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getProductName(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::PRODUCT_NAME,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::PRODUCT_NAME->value),
		);

		$this->setDeviceProperty(
			$device->getId(),
			$message->getModel(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::MODEL->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getMac(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::MAC_ADDRESS->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getSn(),
			ValuesTypes\DataType::STRING,
			TuyaTypes\DevicePropertyIdentifier::SERIAL_NUMBER,
			DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::SERIAL_NUMBER->value),
		);

		$this->databaseHelper->transaction(function () use ($message, $device): bool {
			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->byIdentifier(TuyaTypes\DataPoint::CLOUD);
			$findChannelQuery->forDevice($device);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

			if ($channel === null) {
				$channel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Channel::class,
					'device' => $device,
					'identifier' => TuyaTypes\DataPoint::CLOUD->value,
				]));

				$this->logger->debug(
					'Device channel was created',
					[
						'source' => Sources\Connector::TUYA->value,
						'type' => 'store-cloud-device-message-consumer',
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
					$dataPoint->getScale(),
					$dataPoint->getStep(),
					$dataPoint->isSettable(),
					$dataPoint->isQueryable(),
				);
			}

			return true;
		});

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => Sources\Connector::TUYA->value,
				'type' => 'store-cloud-device-message-consumer',
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
