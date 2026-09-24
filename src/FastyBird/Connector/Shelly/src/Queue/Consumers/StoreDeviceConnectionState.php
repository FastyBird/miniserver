<?php declare(strict_types = 1);

/**
 * StoreDeviceConnectionState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           12.01.23
 */

namespace FastyBird\Connector\Shelly\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
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
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceConnectionState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Shelly\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
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
		$findDeviceQuery->startWithIdentifier($message->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
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
				|| $message->getState() === DevicesTypes\ConnectionState::LOST
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
						Sources\Connector::SHELLY,
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
							Sources\Connector::SHELLY,
						));
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed device connection state message',
			[
				'source' => Sources\Connector::SHELLY->value,
				'type' => 'store-device-connection-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'identifier' => $message->getIdentifier(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
