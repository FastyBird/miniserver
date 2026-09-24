<?php declare(strict_types = 1);

/**
 * SetDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use TypeError;
use ValueError;
use function preg_match;
use function React\Async\await;

/**
 * Store device connection state message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Zigbee2Mqtt\Helpers\Devices\Bridge $bridgeHelper,
		private readonly Zigbee2Mqtt\Helpers\Devices\SubDevice $subDeviceHelper,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDeviceConnectionState) {
			return false;
		}

		if (preg_match('/^0x[a-fA-F0-9]{16}$/', $message->getDevice()) === 1) {
			$findDeviceQuery = new Queries\Configuration\FindSubDevices();
			$findDeviceQuery->byConnectorId($message->getConnector());
			$findDeviceQuery->byIdentifier($message->getDevice());

			$device = $this->devicesConfigurationRepository->findOneBy(
				$findDeviceQuery,
				Documents\Devices\SubDevice::class,
			);

		} else {
			$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
			$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::FRIENDLY_NAME->value);
			$findDevicePropertyQuery->byValue($message->getDevice());

			$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesDocuments\Devices\Properties\Variable::class,
			);

			if ($property === null) {
				return true;
			}

			$findDeviceQuery = new Queries\Configuration\FindSubDevices();
			$findDeviceQuery->byConnectorId($message->getConnector());
			$findDeviceQuery->byId($property->getDevice());

			$device = $this->devicesConfigurationRepository->findOneBy(
				$findDeviceQuery,
				Documents\Devices\SubDevice::class,
			);
		}

		if ($device === null) {
			return true;
		}

		$bridge = $this->subDeviceHelper->getBridge($device);

		if ($this->bridgeHelper->getBaseTopic($bridge) !== $message->getBaseTopic()) {
			return true;
		}

		$state = DevicesTypes\ConnectionState::UNKNOWN;

		if ($message->getState() === Types\ConnectionState::ONLINE) {
			$state = DevicesTypes\ConnectionState::CONNECTED;
		} elseif ($message->getState() === Types\ConnectionState::OFFLINE) {
			$state = DevicesTypes\ConnectionState::DISCONNECTED;
		} elseif ($message->getState() === Types\ConnectionState::ALERT) {
			$state = DevicesTypes\ConnectionState::ALERT;
		}

		// Check device state...
		if ($this->deviceConnectionManager->getState($device) !== $state) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState($device, $state);

			if (
				$state === DevicesTypes\ConnectionState::DISCONNECTED
				|| $state === DevicesTypes\ConnectionState::ALERT
				|| $state === DevicesTypes\ConnectionState::UNKNOWN
			) {
				$findChannelsQuery = new Queries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsConfigurationRepository->findAllBy(
					$findChannelsQuery,
					Documents\Channels\Channel::class,
				);

				foreach ($channels as $channel) {
					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesDocuments\Channels\Properties\Dynamic::class,
					);

					foreach ($properties as $property) {
						await($this->channelPropertiesStatesManager->setValidState(
							$property,
							false,
							Sources\Connector::ZIGBEE2MQTT,
						));
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device connection state message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-device-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
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
