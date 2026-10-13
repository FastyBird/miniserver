<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Documents as SonoffDocuments;
use FastyBird\Connector\Sonoff\Queries as SonoffQueries;
use FastyBird\Connector\Sonoff\Queue;
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
 * Device state message consumer
 */
final readonly class StoreParametersStates implements Queue\Consumer
{

	public function __construct(
		private Sonoff\Logger $logger,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private Helpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	#[Override]
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreParametersStates) {
			return false;
		}

		$findDeviceQuery = new SonoffQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			SonoffDocuments\Devices\Device::class,
		);

		if ($device === null) {
			return true;
		}

		foreach ($message->getParameters() as $parameter) {
			if ($parameter instanceof Queue\Messages\States\DeviceParameterState) {
				$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($parameter->getName());

				$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

				if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
					await($this->devicePropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => $parameter->getValue(),
						]),
						Sources\Connector::SONOFF,
					));
				} elseif ($property instanceof DevicesDocuments\Devices\Properties\Variable) {
					$this->databaseHelper->transaction(
						function () use ($property, $parameter): void {
							$property = $this->devicesPropertiesRepository->find(
								$property->getId(),
								DevicesEntities\Devices\Properties\Variable::class,
							);
							assert($property instanceof DevicesEntities\Devices\Properties\Variable);

							$this->devicesPropertiesManager->update(
								$property,
								Utils\ArrayHash::from([
									'value' => $parameter->getValue(),
								]),
							);
						},
					);
				}
			} elseif ($parameter instanceof Queue\Messages\States\ChannelParameterState) {
				$findChannelQuery = new SonoffQueries\Configuration\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier($parameter->getGroup());

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					SonoffDocuments\Channels\Channel::class,
				);

				if ($channel !== null) {
					$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($parameter->getName());

					$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

					if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
						await($this->channelPropertiesStatesManager->set(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_FIELD => $parameter->getValue(),
							]),
							Sources\Connector::SONOFF,
						));
					} elseif ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
						$this->databaseHelper->transaction(
							function () use ($property, $parameter): void {
								$property = $this->channelsPropertiesRepository->find(
									$property->getId(),
									DevicesEntities\Channels\Properties\Variable::class,
								);
								assert($property instanceof DevicesEntities\Channels\Properties\Variable);

								$this->channelsPropertiesManager->update(
									$property,
									Utils\ArrayHash::from([
										'value' => $parameter->getValue(),
									]),
								);
							},
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => Sources\Connector::SONOFF->value,
				'type' => 'status-parameters-states-message-consumer',
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
