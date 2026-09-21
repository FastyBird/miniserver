<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           17.08.23
 */

namespace FastyBird\Connector\Tuya\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	private const WRITE_PENDING_DELAY = 2_000.0;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Clock $clock,
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
	 * @throws Throwable
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
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
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
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
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
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
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
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
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
				'Channel property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
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

		$expectedValue = ToolsUtilities\Value::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::TUYA,
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
			$property,
			true,
			MetadataTypes\Sources\Connector::TUYA,
		));

		try {
			if ($this->connectorHelper->getClientMode($connector) === Types\ClientMode::CLOUD) {
				$client = $this->connectionManager->getCloudApiConnection($connector);

				if (!$client->isConnected()) {
					$client->connect();
				}

				if ($client->isRefreshFailed()) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId(),
								'device' => $device->getId(),
								'state' => DevicesTypes\ConnectionState::SLEEPING,
							],
						),
					);

					await($this->channelPropertiesStatesManager->setPendingState(
						$property,
						false,
						MetadataTypes\Sources\Connector::TUYA,
					));

					$this->logger->error(
						'Connection with Tuya cloud needs to be refreshed',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'write-channel-property-state-message-consumer',
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

				$result = $client->setDeviceState(
					$device->getIdentifier(),
					$property->getIdentifier(),
					$expectedValue,
				);
			} else {
				$client = $this->connectionManager->getLocalConnection($device);

				$result = $client->writeState(
					$property->getIdentifier(),
					$expectedValue,
					$this->deviceHelper->getGateway($device) !== null ? $device->getIdentifier() : null,
				);
			}
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::TUYA,
			));

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
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
		} catch (Exceptions\OpenApiError | Exceptions\LocalApiError $ex) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::TUYA,
			));

			$this->logger->error(
				'Preparing api request failed',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'write-channel-property-state-message-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
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
		} catch (Exceptions\OpenApiCall | Exceptions\LocalApiCall $ex) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => DevicesTypes\ConnectionState::DISCONNECTED,
					],
				),
			);

			await($this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::TUYA,
			));

			$extra = [];

			if ($ex instanceof Exceptions\OpenApiCall) {
				$extra = [
					'request' => [
						'method' => $ex->getRequest()?->getMethod(),
						'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
						'body' => $ex->getRequest()?->getBody()->getContents(),
					],
					'response' => [
						'body' => $ex->getResponse()?->getBody()->getContents(),
					],
				];
			}

			$this->logger->error(
				'Calling device api failed',
				array_merge(
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'write-channel-property-state-message-consumer',
						'exception' => ToolsHelpers\Logger::buildException($ex, false),
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
					$extra,
				),
			);

			return true;
		}

		$result->then(
			function () use ($connector, $device, $channel, $property, $state, $message): void {
				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					]),
					MetadataTypes\Sources\Connector::TUYA,
				));

				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'write-channel-property-state-message-consumer',
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
			},
			async(function (Throwable $ex) use ($connector, $device, $channel, $property, $message): void {
				await($this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::TUYA,
				));

				$extra = [];

				$renderException = true;

				if ($ex instanceof Exceptions\OpenApiCall || $ex instanceof Exceptions\LocalApiCall) {
					if ($ex instanceof Exceptions\OpenApiCall) {
						$extra = [
							'request' => [
								'method' => $ex->getRequest()?->getMethod(),
								'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
								'body' => $ex->getRequest()?->getBody()->getContents(),
							],
							'response' => [
								'body' => $ex->getResponse()?->getBody()->getContents(),
							],
						];
					}

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);

					$renderException = false;

				} elseif ($ex instanceof Exceptions\OpenApiError || $ex instanceof Exceptions\LocalApiError) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);

				} else {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $connector->getId(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::LOST,
							],
						),
					);
				}

				$this->logger->error(
					'Could write state to device',
					array_merge(
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'write-channel-property-state-message-consumer',
							'exception' => ToolsHelpers\Logger::buildException($ex, $renderException),
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
						$extra,
					),
				);
			}),
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\Sources\Connector::TUYA->value,
				'type' => 'write-channel-property-state-message-consumer',
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
