<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
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

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Models;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_merge;
use function implode;
use function preg_match;
use function React\Async\await;
use function sprintf;

/**
 * Store device state message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\StateRepository $stateRepository,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDeviceState) {
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

		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byValue($message->getDevice());

		if (preg_match(Zigbee2Mqtt\Constants::IEEE_ADDRESS_REGEX, $message->getDevice()) === 1) {
			$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::IEEE_ADDRESS);

		} else {
			$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::FRIENDLY_NAME);
		}

		$deviceTypeProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($deviceTypeProperty === null) {
			return true;
		}

		$findDeviceQuery = new Queries\Configuration\FindSubDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($deviceTypeProperty->getDevice());
		$findDeviceQuery->forParent($bridge);

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\SubDevice::class,
		);

		if ($device === null) {
			return true;
		}

		$this->processStates($bridge, $device, $message->getStates());

		$this->logger->debug(
			'Consumed device state message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-device-state-message-consumer',
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

	/**
	 * @param array<Queue\Messages\SingleExposeData|Queue\Messages\CompositeExposeData> $states
	 * @param array<string> $identifiers
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processStates(
		Documents\Devices\Bridge $bridge,
		Documents\Devices\SubDevice $device,
		array $states,
		array $identifiers = [],
	): void
	{
		foreach ($states as $state) {
			if ($state instanceof Queue\Messages\SingleExposeData) {
				$findChannelQuery = new Queries\Configuration\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->endWithIdentifier(
					sprintf(
						'_%s',
						implode('_', array_merge($identifiers, [$state->getIdentifier()])),
					),
				);

				$channel = $this->channelsConfigurationRepository->findOneBy(
					$findChannelQuery,
					Documents\Channels\Channel::class,
				);

				if ($channel === null) {
					$this->logger->debug(
						'Channel for storing device state could not be loaded',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'store-device-state-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'data' => $state->toArray(),
						],
					);

					continue;
				}

				$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier($state->getIdentifier());

				$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
					$findChannelPropertyQuery,
					DevicesDocuments\Channels\Properties\Dynamic::class,
				);

				if ($property === null) {
					$this->logger->warning(
						'Channel property for storing device state could not be loaded',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'store-device-state-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'data' => $state->toArray(),
						],
					);

					continue;
				}

				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getValue(),
					]),
					Sources\Connector::ZIGBEE2MQTT,
				));

				if ($this->deviceConnectionManager->getState($device) !== DevicesTypes\ConnectionState::CONNECTED) {
					await($this->channelPropertiesStatesManager->setValidState(
						$property,
						false,
						Sources\Connector::ZIGBEE2MQTT,
					));
				}

				$this->stateRepository->set($property->getId(), $state->getValue());

			} else {
				$this->processStates(
					$bridge,
					$device,
					$state->getStates(),
					array_merge($identifiers, [$state->getIdentifier()]),
				);
			}
		}
	}

}
