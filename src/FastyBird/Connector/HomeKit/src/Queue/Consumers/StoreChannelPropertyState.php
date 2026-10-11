<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette\Utils;
use Override;
use TypeError;
use ValueError;
use function assert;
use function React\Async\await;

/**
 * Store channel property state message consumer
 */
final readonly class StoreChannelPropertyState implements Queue\Consumer
{

	public function __construct(
		private HomeKit\Logger $logger,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private Helpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreChannelPropertyState) {
			return false;
		}

		$findDeviceQuery = new HomeKitQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			HomeKitDocuments\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'store-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new HomeKitQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($message->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			HomeKitDocuments\Channels\Channel::class,
		);

		if ($channel === null) {
			$this->logger->error(
				'Device channel could not be loaded',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'store-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($message->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device channel property could not be loaded',
				[
					'source' => Sources\Connector::HOMEKIT->value,
					'type' => 'store-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
			$this->databaseHelper->transaction(
				function () use ($message, $property): void {
					$property = $this->channelsPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Channels\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Channels\Properties\Variable);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $message->getValue(),
						]),
					);
				},
			);

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			await($this->channelPropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $message->getValue(),
				]),
				Sources\Connector::HOMEKIT,
			));
			await($this->channelPropertiesStatesManager->setValidState(
				$property,
				true,
				Sources\Connector::HOMEKIT,
			));
		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findChannelPropertyQuery->byId($property->getParent());

			$parent = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

			if ($parent instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				await($this->channelPropertiesStatesManager->write(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $message->getValue(),
					]),
					Sources\Connector::HOMEKIT,
				));
			} elseif ($parent instanceof DevicesDocuments\Channels\Properties\Variable) {
				$this->databaseHelper->transaction(
					function () use ($message, $parent, $device, $channel, $property): void {
						$toUpdate = $this->channelsPropertiesRepository->find(
							$parent->getId(),
							DevicesEntities\Channels\Properties\Variable::class,
						);

						if ($toUpdate !== null) {
							$this->channelsPropertiesManager->update(
								$toUpdate,
								Utils\ArrayHash::from([
									'value' => $message->getValue(),
								]),
							);
						} else {
							$this->logger->error(
								'Mapped variable property could not be updated',
								[
									'source' => Sources\Connector::HOMEKIT->value,
									'type' => 'store-channel-property-state-message-consumer',
									'connector' => [
										'id' => $message->getConnector()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
									'channel' => [
										'id' => $channel->getId()->toString(),
									],
									'property' => [
										'id' => $property->getId()->toString(),
									],
									'data' => $message->toArray(),
								],
							);
						}
					},
				);
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => Sources\Connector::HOMEKIT->value,
				'type' => 'store-channel-property-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
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
