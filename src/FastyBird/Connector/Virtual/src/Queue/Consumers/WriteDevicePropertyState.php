<?php declare(strict_types = 1);

/**
 * WriteDevicePropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           22.11.23
 */

namespace FastyBird\Connector\Virtual\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Documents;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
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
final class WriteDevicePropertyState implements Queue\Consumer
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
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Clock\Clock $clock,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws RuntimeException
	 * @throws ValueError
	 * @throws TypeError
	 * @throws ApplicationExceptions\InvalidArgument
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteDevicePropertyState) {
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
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
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
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byId($message->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

		if (
			!$property instanceof DevicesDocuments\Devices\Properties\Dynamic
			&& !$property instanceof DevicesDocuments\Devices\Properties\Mapped
		) {
			$this->logger->error(
				'Device property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic && !$property->isSettable()) {
			$this->logger->error(
				'Device property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'write-device-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
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

		if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
			$valueToWrite = $state->getExpectedValue();
		} else {
			$valueToWrite = $state->getExpectedValue() ?? ($state->isValid() ? $state->getActualValue() : null);
		}

		if ($valueToWrite === null) {
			if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				await($this->devicePropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::VIRTUAL,
				));
			}

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

		if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
			await($this->devicePropertiesStatesManager->setPendingState(
				$property,
				true,
				MetadataTypes\Sources\Connector::VIRTUAL,
			));
		}

		try {
			$driver = $this->driversManager->getDriver($device);

			if (!$driver->isConnected()) {
				return true;
			}

			$result = $property instanceof DevicesDocuments\Devices\Properties\Mapped
				? $driver->notifyState($property, $valueToWrite)
				: $driver->writeState($property, $valueToWrite);
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => DevicesTypes\ConnectionState::ALERT,
						'source' => MetadataTypes\Sources\Connector::VIRTUAL,
					],
				),
			);

			if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				await($this->devicePropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::VIRTUAL,
				));
			}

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
					'type' => 'write-device-property-state-message-consumer',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
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
			function () use ($connector, $device, $property, $state, $message): void {
				await($this->devicePropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					]),
					MetadataTypes\Sources\Connector::VIRTUAL,
				));

				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
						'type' => 'write-device-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $message->toArray(),
					],
				);
			},
			async(function (Throwable $ex) use ($connector, $device, $property, $message): void {
				if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
					await($this->devicePropertiesStatesManager->setPendingState(
						$property,
						false,
						MetadataTypes\Sources\Connector::VIRTUAL,
					));
				}

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $connector->getId(),
							'device' => $device->getId(),
							'state' => DevicesTypes\ConnectionState::ALERT,
							'source' => MetadataTypes\Sources\Connector::VIRTUAL,
						],
					),
				);

				$this->logger->error(
					'Could write state to device',
					[
						'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
						'type' => 'write-device-property-state-message-consumer',
						'exception' => ToolsHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
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
				'source' => MetadataTypes\Sources\Connector::VIRTUAL->value,
				'type' => 'write-device-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
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
