<?php declare(strict_types = 1);

/**
 * WriteSubDeviceChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           32.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Models;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Clock;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use stdClass;
use Throwable;
use TypeError;
use ValueError;
use function preg_match;
use function React\Async\async;
use function React\Async\await;
use function sprintf;

/**
 * Write state to sub-device message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteSubDeviceChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	private const WRITE_PENDING_DELAY = 2_000.0;

	public function __construct(
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		protected readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connectors\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly Helpers\Devices\SubDevice $subDeviceHelper,
		private readonly Models\StateRepository $stateRepository,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly Clock\Clock $clock,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteSubDeviceChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($message->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
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

		$findDeviceQuery = new Queries\Configuration\FindSubDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\SubDevice::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$bridge = $this->subDeviceHelper->getBridge($device);

		$findChannelQuery = new Queries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($message->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Channel::class,
		);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'bridge' => [
						'id' => $bridge->getId()->toString(),
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

		$propertyToUpdate = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if (!$propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'bridge' => [
						'id' => $bridge->getId()->toString(),
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

		if (!$propertyToUpdate->isSettable()) {
			$this->logger->warning(
				'Channel property is not writable',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'bridge' => [
						'id' => $bridge->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $propertyToUpdate->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$state = $message->getState();

		if ($state === null) {
			return true;
		}

		if ($state->getExpectedValue() === null) {
			await($this->channelPropertiesStatesManager->setPendingState(
				$propertyToUpdate,
				false,
				Sources\Connector::ZIGBEE2MQTT,
			));

			return true;
		}

		$now = $this->clock->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= self::WRITE_PENDING_DELAY
			)
		) {
			return true;
		}

		await($this->channelPropertiesStatesManager->setPendingState(
			$propertyToUpdate,
			true,
			Sources\Connector::ZIGBEE2MQTT,
		));

		$findPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertiesQuery->forChannel($channel);
		$findPropertiesQuery->settable(true);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
			$findPropertiesQuery,
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);

		$updateCallbacks = [];

		if (
			preg_match(Zigbee2Mqtt\Constants::CHANNEL_IDENTIFIER_REGEX, $channel->getIdentifier(), $matches) === 1
			&& Types\ExposeType::tryFrom($matches['type']) !== null
		) {
			$writeData = new stdClass();

			foreach ($properties as $property) {
				if ($message->getProperty()->equals($property->getId())) {
					$writeData->{$property->getIdentifier()} = Utilities\Value::flattenValue(
						$state->getExpectedValue(),
					);

					$updateCallbacks[] = function () use ($property, $state): void {
						await($this->channelPropertiesStatesManager->set(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
								DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
							]),
							Sources\Connector::ZIGBEE2MQTT,
						));
					};
				} else {
					try {
						$value = $this->stateRepository->get($property->getId());

						$writeData->{$property->getIdentifier()} = Utilities\Value::flattenValue($value);
					} catch (Zigbee2MqttExceptions\MissingValue) {
						// Could be ignored
					}
				}
			}

			if ($matches['type'] === Types\ExposeType::COMPOSITE->value) {
				$payload = new stdClass();
				$payload->{$matches['identifier']} = $writeData;
			} else {
				$payload = $writeData;
			}
		} elseif (
			preg_match(
				Zigbee2Mqtt\Constants::CHANNEL_SPECIAL_IDENTIFIER_REGEX,
				$channel->getIdentifier(),
				$matches,
			) === 1
			&& Types\ExposeType::tryFrom($matches['type']) !== null
			&& Types\ExposeType::tryFrom($matches['subtype']) !== null
		) {
			$writeData = new stdClass();

			foreach ($properties as $property) {
				if ($message->getProperty()->equals($property->getId())) {
					$writeData->{$property->getIdentifier()} = Utilities\Value::flattenValue(
						$state->getExpectedValue(),
					);

					$updateCallbacks[] = function () use ($property, $state): void {
						await($this->channelPropertiesStatesManager->set(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
								DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
							]),
							Sources\Connector::ZIGBEE2MQTT,
						));
					};

				} else {
					try {
						$value = $this->stateRepository->get($property->getId());

						$writeData->{$property->getIdentifier()} = Utilities\Value::flattenValue($value);
					} catch (Zigbee2MqttExceptions\MissingValue) {
						// Could be ignored
					}
				}
			}

			if ($matches['subtype'] === Types\ExposeType::COMPOSITE->value) {
				$payload = new stdClass();
				$payload->{$matches['identifier']} = $writeData;
			} else {
				$payload = $writeData;
			}
		} else {
			await($this->channelPropertiesStatesManager->setPendingState(
				$propertyToUpdate,
				false,
				Sources\Connector::ZIGBEE2MQTT,
			));

			$this->logger->error(
				'Channel identifier has invalid value',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'bridge' => [
						'id' => $bridge->getId()->toString(),
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

		if (!$this->getClient($connector)->isConnected()) {
			$this->logger->error(
				'Client is not connected to Zigbee2MQTT',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'bridge' => [
						'id' => $bridge->getId()->toString(),
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

			return false;
		}

		try {
			$this->getClient($connector)
				->publish(
					sprintf(
						'%s/%s/set',
						$this->bridgeHelper->getBaseTopic($bridge),
						$this->subDeviceHelper->getFriendlyName($device) ?? $this->subDeviceHelper->getIeeeAddress(
							$device,
						),
					),
					Utils\Json::encode($payload),
				)
				->then(function () use ($connector, $bridge, $device, $channel, $updateCallbacks, $message): void {
					Utils\Arrays::invoke($updateCallbacks);

					$this->logger->debug(
						'Channel state was successfully sent to device',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'write-sub-device-channel-property-state-message-consumer',
							'connector' => [
								'id' => $connector->getId()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
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
				})
				->catch(
					async(
						function (Throwable $ex) use ($connector, $propertyToUpdate, $bridge, $device, $channel, $message): void {
							await($this->channelPropertiesStatesManager->setPendingState(
								$propertyToUpdate,
								false,
								Sources\Connector::ZIGBEE2MQTT,
							));

							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $connector->getId(),
										'base_topic' => $this->bridgeHelper->getBaseTopic($bridge),
										'identifier' => $bridge->getIdentifier(),
										'state' => Types\ConnectionState::UNKNOWN->value,
									],
								),
							);

							$this->logger->error(
								'Could write state to sub-device',
								[
									'source' => Sources\Connector::ZIGBEE2MQTT->value,
									'type' => 'write-sub-device-channel-property-state-message-consumer',
									'exception' => Logging\Logger::buildException($ex),
									'connector' => [
										'id' => $connector->getId()->toString(),
									],
									'bridge' => [
										'id' => $bridge->getId()->toString(),
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
						},
					),
				);
		} catch (Throwable $ex) {
			await($this->channelPropertiesStatesManager->setPendingState(
				$propertyToUpdate,
				false,
				Sources\Connector::ZIGBEE2MQTT,
			));

			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'write-sub-device-channel-property-state-message-consumer',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'bridge' => [
						'id' => $bridge->getId()->toString(),
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
		}

		$this->logger->debug(
			'Consumed write sub-device state message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'write-sub-device-channel-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getClient(Documents\Connectors\Connector $connector): API\Client
	{
		return $this->connectionManager->getClient(
			$connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($connector),
			$this->connectorHelper->getServerPort($connector),
			$this->connectorHelper->getUsername($connector),
			$this->connectorHelper->getPassword($connector),
		);
	}

}
