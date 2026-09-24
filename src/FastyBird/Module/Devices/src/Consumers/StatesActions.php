<?php declare(strict_types = 1);

/**
 * StatesActions.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Consumers;

use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Exchange\Publisher\Async;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette\Utils;
use Throwable;
use TypeError;
use ValueError;
use function in_array;
use function React\Async\await;

/**
 * States actions messages subscriber
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StatesActions implements Consumers\Consumer
{

	private const CONSUMER_ROUTING_KEYS = [
		Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
		Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY,
	];

	public function __construct(
		private readonly Devices\Logger $logger,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\Async\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Async\MessagePublisher $publisher,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(
		Sources\Source $source,
		string $routingKey,
		CoreDocuments\Document|null $document,
	): void
	{
		if ($document === null) {
			return;
		}

		if (in_array($routingKey, self::CONSUMER_ROUTING_KEYS, true)) {
			$this->handlePropertyStateAction($document, $source, $routingKey);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handlePropertyStateAction(
		CoreDocuments\Document $document,
		Sources\Source $source,
		string $routingKey,
	): void
	{
		if ($document instanceof DevicesDocuments\States\Connectors\Properties\Actions\Action) {
			if ($document->getAction() === Types\PropertyAction::SET) {
				$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
				$findConnectorPropertyQuery->byId($document->getProperty());

				$property = $this->connectorPropertiesConfigurationRepository->findOneBy(
					$findConnectorPropertyQuery,
					DevicesDocuments\Connectors\Properties\Dynamic::class,
				);

				if ($property === null) {
					return;
				}

				$result = null;
				$data = [];

				if ($document->getSet() !== null) {
					if ($document->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::ACTUAL_VALUE_FIELD] = $document->getSet()->getActualValue();
					}

					if ($document->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::EXPECTED_VALUE_FIELD] = $document->getSet()->getExpectedValue();
					}

					if ($data !== []) {
						$result = $this->connectorPropertiesStatesManager->writeState(
							$property,
							Utils\ArrayHash::from($data),
							false,
							$source,
						);
					}
				} elseif ($document->getWrite() !== null) {
					if ($document->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::ACTUAL_VALUE_FIELD] = $document->getWrite()->getActualValue();
					}

					if ($document->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::EXPECTED_VALUE_FIELD] = $document->getWrite()->getExpectedValue();
					}

					if ($data !== []) {
						$result = $this->connectorPropertiesStatesManager->writeState(
							$property,
							Utils\ArrayHash::from($data),
							true,
							$source,
						);
					}
				}

				$result
					?->then(function () use ($document, $property, $source, $routingKey, $data): void {
						$this->logger->debug(
							'Requested write value to connector property',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'state-consumer',
								'connector' => [
									'id' => $document->getConnector()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
									'identifier' => $property->getIdentifier(),
								],
								'data' => $data,
								'message' => [
									'routing_key' => $routingKey,
									'source' => $source->value,
									'data' => $document->toArray(),
								],
							],
						);
					});
			} elseif ($document->getAction() === Types\PropertyAction::GET) {
				$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
				$findConnectorPropertyQuery->byId($document->getProperty());

				$property = $this->connectorPropertiesConfigurationRepository->findOneBy(
					$findConnectorPropertyQuery,
					DevicesDocuments\Connectors\Properties\Dynamic::class,
				);

				if ($property === null) {
					return;
				}

				$state = await($this->connectorPropertiesStatesManager->readState($property));

				if ($state === null) {
					return;
				}

				$this->publisher->publish(
					Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					$state,
				)
					->then(function () use ($document, $property, $source, $routingKey): void {
						$this->logger->debug(
							'Requested write value to channel property',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'state-consumer',
								'connector' => [
									'id' => $document->getConnector()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
									'identifier' => $property->getIdentifier(),
								],
								'message' => [
									'routing_key' => $routingKey,
									'source' => $source->value,
									'data' => $document->toArray(),
								],
							],
						);
					})
					->catch(function (Throwable $ex): void {
						$this->logger->error(
							'Requested action could not be published for write action',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'channel-properties-states',
								'exception' => Logging\Logger::buildException($ex),
							],
						);
					});
			}
		} elseif ($document instanceof DevicesDocuments\States\Devices\Properties\Actions\Action) {
			if ($document->getAction() === Types\PropertyAction::SET) {
				$findConnectorPropertyQuery = new Queries\Configuration\FindDeviceProperties();
				$findConnectorPropertyQuery->byId($document->getProperty());

				$property = $this->devicePropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

				if (
					!$property instanceof DevicesDocuments\Devices\Properties\Dynamic
					&& !$property instanceof DevicesDocuments\Devices\Properties\Mapped
				) {
					return;
				}

				$result = null;
				$data = [];

				if ($document->getSet() !== null) {
					if ($document->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::ACTUAL_VALUE_FIELD] = $document->getSet()->getActualValue();
					}

					if ($document->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::EXPECTED_VALUE_FIELD] = $document->getSet()->getExpectedValue();
					}

					if ($data !== []) {
						$result = $this->devicePropertiesStatesManager->writeState(
							$property,
							Utils\ArrayHash::from($data),
							false,
							$source,
						);
					}
				} elseif ($document->getWrite() !== null) {
					if ($document->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::ACTUAL_VALUE_FIELD] = $document->getWrite()->getActualValue();
					}

					if ($document->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::EXPECTED_VALUE_FIELD] = $document->getWrite()->getExpectedValue();
					}

					if ($data !== []) {
						$result = $this->devicePropertiesStatesManager->writeState(
							$property,
							Utils\ArrayHash::from($data),
							true,
							$source,
						);
					}
				}

				$result
					?->then(function () use ($document, $property, $source, $routingKey, $data): void {
						$this->logger->debug(
							'Requested write value to device property',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'state-consumer',
								'device' => [
									'id' => $document->getDevice()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
									'identifier' => $property->getIdentifier(),
								],
								'data' => $data,
								'message' => [
									'routing_key' => $routingKey,
									'source' => $source->value,
									'data' => $document->toArray(),
								],
							],
						);
					});
			} elseif ($document->getAction() === Types\PropertyAction::GET) {
				$findConnectorPropertyQuery = new Queries\Configuration\FindDeviceProperties();
				$findConnectorPropertyQuery->byId($document->getProperty());

				$property = $this->devicePropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

				if (
					!$property instanceof DevicesDocuments\Devices\Properties\Dynamic
					&& !$property instanceof DevicesDocuments\Devices\Properties\Mapped
				) {
					return;
				}

				$state = await($this->devicePropertiesStatesManager->readState($property));

				if ($state === null) {
					return;
				}

				$this->publisher->publish(
					Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					$state,
				)
					->then(function () use ($document, $property, $source, $routingKey): void {
						$this->logger->debug(
							'Requested write value to channel property',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'state-consumer',
								'device' => [
									'id' => $document->getDevice()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
									'identifier' => $property->getIdentifier(),
								],
								'message' => [
									'routing_key' => $routingKey,
									'source' => $source->value,
									'data' => $document->toArray(),
								],
							],
						);
					})
					->catch(function (Throwable $ex): void {
						$this->logger->error(
							'Requested action could not be published for write action',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'channel-properties-states',
								'exception' => Logging\Logger::buildException($ex),
							],
						);
					});
			}
		} elseif ($document instanceof DevicesDocuments\States\Channels\Properties\Actions\Action) {
			if ($document->getAction() === Types\PropertyAction::SET) {
				$findConnectorPropertyQuery = new Queries\Configuration\FindChannelProperties();
				$findConnectorPropertyQuery->byId($document->getProperty());

				$property = $this->channelPropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

				if (
					!$property instanceof DevicesDocuments\Channels\Properties\Dynamic
					&& !$property instanceof DevicesDocuments\Channels\Properties\Mapped
				) {
					return;
				}

				$result = null;
				$data = [];

				if ($document->getSet() !== null) {
					if ($document->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::ACTUAL_VALUE_FIELD] = $document->getSet()->getActualValue();
					}

					if ($document->getSet()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::EXPECTED_VALUE_FIELD] = $document->getSet()->getExpectedValue();
					}

					if ($data !== []) {
						$result = $this->channelPropertiesStatesManager->writeState(
							$property,
							Utils\ArrayHash::from($data),
							false,
							$source,
						);
					}
				} elseif ($document->getWrite() !== null) {
					if ($document->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::ACTUAL_VALUE_FIELD] = $document->getWrite()->getActualValue();
					}

					if ($document->getWrite()->getExpectedValue() !== Metadata\Constants::VALUE_NOT_SET) {
						$data[States\Property::EXPECTED_VALUE_FIELD] = $document->getWrite()->getExpectedValue();
					}

					if ($data !== []) {
						$result = $this->channelPropertiesStatesManager->writeState(
							$property,
							Utils\ArrayHash::from($data),
							true,
							$source,
						);
					}
				}

				$result
					?->then(function () use ($document, $property, $source, $routingKey, $data): void {
						$this->logger->debug(
							'Requested write value to channel property',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'state-consumer',
								'channel' => [
									'id' => $document->getChannel()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
									'identifier' => $property->getIdentifier(),
								],
								'data' => $data,
								'message' => [
									'routing_key' => $routingKey,
									'source' => $source->value,
									'data' => $document->toArray(),
								],
							],
						);
					});
			} elseif ($document->getAction() === Types\PropertyAction::GET) {
				$findConnectorPropertyQuery = new Queries\Configuration\FindChannelProperties();
				$findConnectorPropertyQuery->byId($document->getProperty());

				$property = $this->channelPropertiesConfigurationRepository->findOneBy($findConnectorPropertyQuery);

				if (
					!$property instanceof DevicesDocuments\Channels\Properties\Dynamic
					&& !$property instanceof DevicesDocuments\Channels\Properties\Mapped
				) {
					return;
				}

				$state = await($this->channelPropertiesStatesManager->readState($property));

				if ($state === null) {
					return;
				}

				$this->publisher->publish(
					Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
					$state,
				)
					->then(function () use ($document, $property, $source, $routingKey): void {
						$this->logger->debug(
							'Requested write value to channel property',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'state-consumer',
								'channel' => [
									'id' => $document->getChannel()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
									'identifier' => $property->getIdentifier(),
								],
								'message' => [
									'routing_key' => $routingKey,
									'source' => $source->value,
									'data' => $document->toArray(),
								],
							],
						);
					})
					->catch(function (Throwable $ex): void {
						$this->logger->error(
							'Requested action could not be published for write action',
							[
								'source' => Sources\Module::DEVICES->value,
								'type' => 'channel-properties-states',
								'exception' => Logging\Logger::buildException($ex),
							],
						);
					});
			}
		}
	}

}
