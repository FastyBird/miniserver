<?php declare(strict_types = 1);

/**
 * WriteV1ChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           03.12.23
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Core\Clock;
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
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Write V1 protocol state to device message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteV1ChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Connector $connectorHelper,
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Clock\Clock $clock,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws RuntimeException
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteChannelPropertyState) {
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
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-channel-property-state-message-consumer',
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

		if ($this->connectorHelper->getProtocolVersion($connector) !== Types\ProtocolVersion::VERSION_1) {
			return false;
		}

		$findDeviceQuery = new Queries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Device::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-channel-property-state-message-consumer',
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
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($message->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findChannelPropertyQuery,
			DevicesDocuments\Channels\Properties\Dynamic::class,
		);

		if ($property === null) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		if (!$property->isSettable()) {
			$this->logger->warning(
				'Property is not writable',
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
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

		$state = $message->getState();

		if ($state === null) {
			return true;
		}

		$expectedValue = Utilities\Value::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				Sources\Connector::FB_MQTT,
			));

			return true;
		}

		$now = $this->clock->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= FbMqtt\Constants::WRITE_DEBOUNCE_DELAY
			)
		) {
			return true;
		}

		await($this->channelPropertiesStatesManager->setPendingState(
			$property,
			true,
			Sources\Connector::FB_MQTT,
		));

		$topic = API\V1Builder::buildChannelPropertyTopic($device, $channel, $property);

		$this->connectionManager
			->getConnection($connector)
			->publish(
				$topic,
				strval($expectedValue),
			)
			->then(function () use ($connector, $device, $channel, $property, $state, $message): void {
				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					]),
					Sources\Connector::FB_MQTT,
				));

				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => Sources\Connector::FB_MQTT->value,
						'type' => 'write-v1-channel-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
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
			})
			->catch(async(function (Throwable $ex) use ($connector, $device, $channel, $property, $message): void {
				await($this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					Sources\Connector::FB_MQTT,
				));

				$this->logger->error(
					'Could write state to device',
					[
						'source' => Sources\Connector::FB_MQTT->value,
						'type' => 'write-v1-channel-property-state-message-consumer',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $connector->getId()->toString(),
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
			}));

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => Sources\Connector::FB_MQTT->value,
				'type' => 'write-v1-channel-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
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
