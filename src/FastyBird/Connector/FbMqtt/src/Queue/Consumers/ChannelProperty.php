<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Documents as FbMqttDocuments;
use FastyBird\Connector\FbMqtt\Exceptions as FbMqttExceptions;
use FastyBird\Connector\FbMqtt\Queries as FbMqttQueries;
use FastyBird\Connector\FbMqtt\Queue;
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
use Throwable;
use function assert;
use function count;
use function React\Async\await;
use function sprintf;

/**
 * Device channel property MQTT message consumer
 */
final readonly class ChannelProperty implements Queue\Consumer
{

	use HandlesPropertyConfiguration;

	public function __construct(
		private FbMqtt\Logger $logger,
		private DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
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
	 * @throws FbMqttExceptions\ParseMessage
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Throwable
	 */
	#[Override]
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\ChannelProperty) {
			return false;
		}

		$findDeviceQuery = new FbMqttQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			FbMqttDocuments\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $message->getDevice()),
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'channel-property-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $message->getDevice(),
					],
					'channel' => [
						'identifier' => $message->getChannel(),
					],
				],
			);

			return true;
		}

		$findChannelQuery = new FbMqttQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($message->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			FbMqttDocuments\Channels\Channel::class,
		);

		if ($channel === null) {
			$this->logger->warning(
				sprintf('Device channel "%s" is not registered', $message->getChannel()),
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'channel-property-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'identifier' => $message->getChannel(),
					],
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier($message->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if ($property === null) {
			$this->logger->warning(
				sprintf('Property "%s" is not registered', $message->getProperty()),
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'channel-property-message-consumer',
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
						'identifier' => $message->getProperty(),
					],
				],
			);

			return true;
		}

		if ($message->getValue() !== Queue\Messages\Property::VALUE_NOT_SET) {
			if ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
				$this->databaseHelper->transaction(function () use ($message, $property): void {
					$property = $this->channelsPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Channels\Properties\Property);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $message->getValue(),
						]),
					);
				});
			} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $message->getValue(),
					]),
					Sources\Connector::FB_MQTT,
				));
			}
		} else {
			if (count($message->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($message, $property): void {
					$property = $this->channelsPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Channels\Properties\Property);

					$toUpdate = $this->handlePropertyConfiguration($message);

					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => Sources\Connector::FB_MQTT->value,
				'type' => 'channel-property-message-consumer',
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
