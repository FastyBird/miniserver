<?php declare(strict_types = 1);

/**
 * SetDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
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
use function React\Async\await;

/**
 * Store device connection state message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly NsPanel\Logger $logger,
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
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
					'source' => Sources\Connector::NS_PANEL->value,
					'type' => 'store-device-connection-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		// Check device state...
		if (
			$this->deviceConnectionManager->getState($device) !== $message->getState()
		) {
			// ... and if it is not ready, set it to ready
			$this->deviceConnectionManager->setState(
				$device,
				$message->getState(),
			);

			if (
				$message->getState() === DevicesTypes\ConnectionState::DISCONNECTED
				|| $message->getState() === DevicesTypes\ConnectionState::ALERT
				|| $message->getState() === DevicesTypes\ConnectionState::UNKNOWN
			) {
				$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDevicePropertiesQuery->forDevice($device);

				$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
					$findDevicePropertiesQuery,
					DevicesDocuments\Devices\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					await($this->devicePropertiesStatesManager->setValidState(
						$property,
						false,
						Sources\Connector::NS_PANEL,
					));
				}

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
							Sources\Connector::NS_PANEL,
						));
					}
				}
			}

			if ($device instanceof Documents\Devices\Gateway) {
				if (
					$message->getState() === DevicesTypes\ConnectionState::DISCONNECTED
					|| $message->getState() === DevicesTypes\ConnectionState::ALERT
					|| $message->getState() === DevicesTypes\ConnectionState::UNKNOWN
				) {
					$findChildrenDevicesQuery = new Queries\Configuration\FindSubDevices();
					$findChildrenDevicesQuery->forParent($device);

					$children = $this->devicesConfigurationRepository->findAllBy(
						$findChildrenDevicesQuery,
						Documents\Devices\SubDevice::class,
					);

					foreach ($children as $child) {
						$this->deviceConnectionManager->setState(
							$child,
							$message->getState(),
						);

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
								Sources\Connector::NS_PANEL,
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
									Sources\Connector::NS_PANEL,
								));
							}
						}
					}
				}

				if ($message->getState() === DevicesTypes\ConnectionState::ALERT) {
					$findChildrenDevicesQuery = new Queries\Configuration\FindThirdPartyDevices();
					$findChildrenDevicesQuery->forParent($device);

					$children = $this->devicesConfigurationRepository->findAllBy(
						$findChildrenDevicesQuery,
						Documents\Devices\ThirdPartyDevice::class,
					);

					foreach ($children as $child) {
						$this->deviceConnectionManager->setState(
							$child,
							$message->getState(),
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device connection state message',
			[
				'source' => Sources\Connector::NS_PANEL->value,
				'type' => 'store-device-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $message->getDevice()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
