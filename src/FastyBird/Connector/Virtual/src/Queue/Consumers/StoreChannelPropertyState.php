<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Override;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use TypeError;
use ValueError;
use function array_merge;
use function assert;
use function is_string;
use function React\Async\await;

/**
 * Store channel property state message consumer
 */
final readonly class StoreChannelPropertyState implements Queue\Consumer
{

	public function __construct(
		private Virtual\Logger $logger,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private Helpers\Database $databaseHelper,
		private DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreChannelPropertyState) {
			return false;
		}

		$findDeviceQuery = new VirtualQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			VirtualDocuments\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => $message->getSource()->value,
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
					'property' => array_merge(
						is_string($message->getProperty()) ? ['identifier' => $message->getProperty()] : [],
						!is_string($message->getProperty()) ? ['id' => $message->getProperty()->toString()] : [],
					),
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new VirtualQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($message->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			VirtualDocuments\Channels\Channel::class,
		);

		if ($channel === null) {
			$this->logger->error(
				'Device channel could not be loaded',
				[
					'source' => $message->getSource()->value,
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
					'property' => array_merge(
						is_string($message->getProperty()) ? ['identifier' => $message->getProperty()] : [],
						!is_string($message->getProperty()) ? ['id' => $message->getProperty()->toString()] : [],
					),
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		if (is_string($message->getProperty())) {
			$findChannelPropertyQuery->byIdentifier($message->getProperty());
		} else {
			$findChannelPropertyQuery->byId($message->getProperty());
		}

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if ($property === null) {
			$this->logger->error(
				'Device channel property could not be loaded',
				[
					'source' => $message->getSource()->value,
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
					'property' => array_merge(
						is_string($message->getProperty()) ? ['identifier' => $message->getProperty()] : [],
						!is_string($message->getProperty()) ? ['id' => $message->getProperty()->toString()] : [],
					),
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
						[
							'value' => $message->getValue(),
						],
					);
				},
			);

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			await($this->channelPropertiesStatesManager->set(
				$property,
				DevicesStates\PropertyUpdate::create()
					->withActualValue($message->getValue()),
				$message->getSource(),
			));
		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findChannelPropertyQuery->byId($property->getParent());

			$parent = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

			if ($parent instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				await($this->channelPropertiesStatesManager->write(
					$property,
					DevicesStates\PropertyUpdate::create()
						->withExpectedValue($message->getValue()),
					$message->getSource(),
				));
			} elseif ($parent instanceof DevicesDocuments\Channels\Properties\Variable) {
				$this->databaseHelper->transaction(
					function () use ($message, $device, $channel, $property, $parent): void {
						$toUpdate = $this->channelsPropertiesRepository->find(
							$parent->getId(),
							DevicesEntities\Channels\Properties\Variable::class,
						);

						if ($toUpdate !== null) {
							$this->channelsPropertiesManager->update(
								$toUpdate,
								[
									'value' => $message->getValue(),
								],
							);
						} else {
							$this->logger->error(
								'Mapped variable property could not be updated',
								[
									'source' => $message->getSource()->value,
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
				'source' => $message->getSource()->value,
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
