<?php declare(strict_types = 1);

/**
 * StoreBridgeConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
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
use function React\Async\await;

/**
 * Store bridge connection state message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreBridgeConnectionState) {
			return false;
		}

		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($message->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$findDeviceQuery = new Queries\Configuration\FindBridgeDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($baseTopicProperty->getDevice());

		$bridge = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Bridge::class,
		);

		if ($bridge === null) {
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
		if ($this->deviceConnectionManager->getState($bridge) !== $state) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState($bridge, $state);

			if (
				$state === DevicesTypes\ConnectionState::DISCONNECTED
				|| $state === DevicesTypes\ConnectionState::ALERT
				|| $state === DevicesTypes\ConnectionState::UNKNOWN
			) {
				$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($bridge);

				$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
					$findDevicePropertiesQuery,
					DevicesDocuments\Devices\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					await($this->devicePropertiesStatesManager->setValidState(
						$property,
						false,
						Sources\Connector::ZIGBEE2MQTT,
					));
				}

				$findChannelsQuery = new Queries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($bridge);

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

				$findChildrenDevicesQuery = new Queries\Configuration\FindSubDevices();
				$findChildrenDevicesQuery->forParent($bridge);

				$children = $this->devicesConfigurationRepository->findAllBy(
					$findChildrenDevicesQuery,
					Documents\Devices\SubDevice::class,
				);

				foreach ($children as $child) {
					$this->deviceConnectionManager->setState($child, $state);

					$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
					$findDevicePropertiesQuery->forDevice($child);

					$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
						$findDevicePropertiesQuery,
						DevicesDocuments\Devices\Properties\Dynamic::class,
					);

					foreach ($properties as $property) {
						await($this->devicePropertiesStatesManager->setValidState(
							$property,
							false,
							Sources\Connector::ZIGBEE2MQTT,
						));
					}

					$findChannelsQuery = new Queries\Configuration\FindChannels();
					$findChannelsQuery->forDevice($child);

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
		}

		$this->logger->debug(
			'Consumed bridge connection state message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-bridge-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
