<?php declare(strict_types = 1);

/**
 * WriteV1DevicePropertyState.php
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
final class WriteV1DevicePropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Connector $connectorHelper,
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
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
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-property-state-message-consumer',
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
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-property-state-message-consumer',
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

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byId($message->getProperty());

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesDocuments\Devices\Properties\Dynamic::class,
		);

		if ($property === null) {
			$this->logger->error(
				'Device property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-property-state-message-consumer',
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

		if (!$property->isSettable()) {
			$this->logger->warning(
				'Property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
					'type' => 'write-v1-property-state-message-consumer',
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

		$expectedValue = ToolsUtilities\Value::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			await($this->devicePropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::FB_MQTT,
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

		await($this->devicePropertiesStatesManager->setPendingState(
			$property,
			true,
			MetadataTypes\Sources\Connector::FB_MQTT,
		));

		$topic = API\V1Builder::buildDevicePropertyTopic($device, $property);

		$this->connectionManager
			->getConnection($connector)
			->publish(
				$topic,
				strval($expectedValue),
			)
			->then(function () use ($connector, $device, $property, $state, $message): void {
				await($this->devicePropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getExpectedValue(),
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					]),
					MetadataTypes\Sources\Connector::FB_MQTT,
				));

				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
						'type' => 'write-v1-property-state-message-consumer',
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
			})
			->catch(async(function (Throwable $ex) use ($connector, $device, $property, $message): void {
				await($this->devicePropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::FB_MQTT,
				));

				$this->logger->error(
					'Could write state to device',
					[
						'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
						'type' => 'write-v1-property-state-message-consumer',
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
			}));

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT->value,
				'type' => 'write-v1-property-state-message-consumer',
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
