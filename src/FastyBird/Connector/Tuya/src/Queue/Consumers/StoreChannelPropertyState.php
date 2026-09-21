<?php declare(strict_types = 1);

/**
 * StoreChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Formats\Tools as ToolsFormats;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;
use function array_key_exists;
use function array_merge;
use function assert;
use function React\Async\await;
use function strval;

/**
 * Store channel property state message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	/** @var array<string, Uuid\UuidInterface> */
	private array $dataPointsToProperties = [];

	public function __construct(
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Throwable
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreChannelPropertyState) {
			return false;
		}

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'store-channel-property-state-message-consumer',
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

		foreach ($message->getDataPoints() as $dataPoint) {
			$property = $this->findProperty(
				$message->getConnector(),
				$message->getIdentifier(),
				$dataPoint->getCode(),
			);

			if ($property !== null) {
				try {
					await($this->channelPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => $dataPoint->getValue(),
						]),
						MetadataTypes\Sources\Connector::TUYA,
					));
				} catch (ApplicationExceptions\InvalidArgument $ex) {
					$format = $property->getFormat();

					if (
						$property->getDataType() === MetadataTypes\DataType::ENUM
						&& $dataPoint->getValue() !== null
						&& $format instanceof ToolsFormats\StringEnum
					) {
						$property = $this->databaseHelper->transaction(
							function () use ($dataPoint, $property, $format): DevicesEntities\Channels\Properties\Dynamic {
								$property = $this->channelsPropertiesRepository->find(
									$property->getId(),
									DevicesEntities\Channels\Properties\Dynamic::class,
								);
								assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

								$updated = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
									'format' => array_merge(
										$format->toArray(),
										[Utils\Strings::lower(strval($dataPoint->getValue()))],
									),
								]));
								assert($updated instanceof DevicesEntities\Channels\Properties\Dynamic);

								return $updated;
							},
						);

						$property = $this->channelsPropertiesConfigurationRepository->find($property->getId());
						assert($property instanceof DevicesDocuments\Channels\Properties\Dynamic);

					} else {
						throw $ex;
					}

					await($this->channelPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => $dataPoint->getValue(),
						]),
						MetadataTypes\Sources\Connector::TUYA,
					));
				}
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\Sources\Connector::TUYA->value,
				'type' => 'store-channel-property-state-message-consumer',
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function findProperty(
		Uuid\UuidInterface $connector,
		string $deviceIdentifier,
		string $dataPointIdentifier,
	): DevicesDocuments\Channels\Properties\Dynamic|null
	{
		$key = $deviceIdentifier . '-' . $dataPointIdentifier;

		if (array_key_exists($key, $this->dataPointsToProperties)) {
			$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findPropertyQuery->byId($this->dataPointsToProperties[$key]);

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findPropertyQuery,
				DevicesDocuments\Channels\Properties\Dynamic::class,
			);

			if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				return $property;
			}
		}

		$property = $this->loadProperty($connector, $deviceIdentifier, $dataPointIdentifier);

		if ($property !== null) {
			$this->dataPointsToProperties[$key] = $property->getId();
		}

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function loadProperty(
		Uuid\UuidInterface $connectorId,
		string $deviceIdentifier,
		string $dataPointIdentifier,
	): DevicesDocuments\Channels\Properties\Dynamic|null
	{
		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($connectorId);
		$findDeviceQuery->byIdentifier($deviceIdentifier);

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			return null;
		}

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsConfigurationRepository->findAllBy(
			$findChannelsQuery,
			Documents\Channels\Channel::class,
		);

		foreach ($channels as $channel) {
			$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findPropertyQuery->forChannel($channel);

			foreach (
				$this->channelsPropertiesConfigurationRepository->findAllBy(
					$findPropertyQuery,
					DevicesDocuments\Channels\Properties\Dynamic::class,
				) as $property
			) {
				if ($property->getIdentifier() === $dataPointIdentifier) {
					return $property;
				}
			}
		}

		return null;
	}

}
