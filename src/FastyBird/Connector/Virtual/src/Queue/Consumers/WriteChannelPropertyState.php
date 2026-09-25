<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.10.23
 */

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
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
use function React\Async\async;
use function React\Async\await;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:VirtualConnector!
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
		private readonly Drivers\DriversManager $driversManager,
		private readonly Virtual\Helpers\MessageBuilder $messageBuilder,
		private readonly Virtual\Logger $logger,
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 * @throws RuntimeException
	 * @throws ValueError
	 * @throws TypeError
	 * @throws CoreExceptions\InvalidArgument
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
					'source' => Sources\Connector::VIRTUAL->value,
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
					'source' => Sources\Connector::VIRTUAL->value,
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
					'source' => Sources\Connector::VIRTUAL->value,
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

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($message->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if (
			!$property instanceof DevicesDocuments\Channels\Properties\Dynamic
			&& !$property instanceof DevicesDocuments\Channels\Properties\Mapped
		) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => Sources\Connector::VIRTUAL->value,
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

		if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic && !$property->isSettable()) {
			$this->logger->error(
				'Channel property is not writable',
				[
					'source' => Sources\Connector::VIRTUAL->value,
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

		if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$valueToWrite = $state->getExpectedValue();
		} else {
			$valueToWrite = $state->getExpectedValue() ?? ($state->isValid() ? $state->getActualValue() : null);
		}

		if ($valueToWrite === null) {
			if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				await($this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					Sources\Connector::VIRTUAL,
				));
			}

			return true;
		}

		if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
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
				Sources\Connector::VIRTUAL,
			));
		}

		try {
			$driver = $this->driversManager->getDriver($device);

			if (!$driver->isConnected()) {
				return true;
			}

			$result = $property instanceof DevicesDocuments\Channels\Properties\Mapped
				? $driver->notifyState($property, $valueToWrite)
				: $driver->writeState($property, $valueToWrite);
		} catch (VirtualExceptions\InvalidState $ex) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
						'source' => Sources\Connector::VIRTUAL,
					],
				),
			);

			if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				await($this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					Sources\Connector::VIRTUAL,
				));
			}

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => Sources\Connector::VIRTUAL->value,
					'type' => 'write-channel-property-state-message-consumer',
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
					Sources\Connector::VIRTUAL,
				));

				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => Sources\Connector::VIRTUAL->value,
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
				if ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
					await($this->channelPropertiesStatesManager->setPendingState(
						$property,
						false,
						Sources\Connector::VIRTUAL,
					));
				}

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $connector->getId(),
							'device' => $device->getId(),
							'state' => DevicesTypes\ConnectionState::ALERT,
							'source' => Sources\Connector::VIRTUAL,
						],
					),
				);

				$this->logger->error(
					'Could write state to device',
					[
						'source' => Sources\Connector::VIRTUAL->value,
						'type' => 'write-channel-property-state-message-consumer',
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
			}),
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => Sources\Connector::VIRTUAL->value,
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
