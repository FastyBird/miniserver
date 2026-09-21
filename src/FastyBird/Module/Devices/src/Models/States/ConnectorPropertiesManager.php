<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           23.08.22
 */

namespace FastyBird\Module\Devices\Models\States;

use DateTimeInterface;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Messaging\Exchange\Publisher as ExchangePublisher;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette;
use Nette\Caching as NetteCaching;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\EventDispatcher as PsrEventDispatcher;
use Ramsey\Uuid;
use Throwable;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;
use function assert;
use function is_array;
use function strval;

/**
 * Useful connector dynamic property state helpers
 *
 * @extends PropertiesManager<Documents\Connectors\Properties\Dynamic, null, States\ConnectorProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesManager extends PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Models\States\Connectors\Repository $connectorPropertyStateRepository,
		private readonly Models\States\Connectors\Manager $connectorPropertiesStatesManager,
		private readonly Caching\Container $moduleCaching,
		private readonly DateTimeFactory\Clock $clock,
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
		private readonly ExchangePublisher\Publisher $publisher,
		Devices\Logger $logger,
		ObjectMapper\Processing\Processor $stateMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct($logger, $stateMapper);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function read(
		Documents\Connectors\Properties\Dynamic $property,
		MetadataTypes\Sources\Source|null $source,
	): bool|Documents\States\Connectors\Properties\Property|null
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						Documents\States\Connectors\Properties\Actions\Action::class,
						[
							'action' => Types\PropertyAction::GET->value,
							'connector' => $property->getConnector()->toString(),
							'property' => $property->getId()->toString(),
						],
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested action could not be published for write action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$document = $this->moduleCaching->getStateCache()->load(
				'read_' . $property->getId()->toString(),
				fn () => $this->readState($property),
				[
					NetteCaching\Cache::Tags => [$property->getId()->toString()],
				],
			);
			assert($document instanceof Documents\States\Connectors\Properties\Property || $document === null);

			return $document;
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function write(
		Documents\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						Documents\States\Connectors\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'connector' => $property->getConnector()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'write' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $item): bool|int|float|string|null => ToolsUtilities\Value::flattenValue(
										$item,
									),
									(array) $data,
								),
							],
						),
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested value could not be published for write action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$this->writeState($property, $data, true, $source);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function set(
		Documents\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						Documents\States\Connectors\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'connector' => $property->getConnector()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'set' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $item): bool|int|float|string|null => ToolsUtilities\Value::flattenValue(
										$item,
									),
									(array) $data,
								),
							],
						),
					),
				);
			} catch (Throwable $ex) {
				throw new Exceptions\InvalidState(
					'Requested value could not be published for set action',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			$this->writeState($property, $data, false, $source);
		}
	}

	/**
	 * @param Documents\Connectors\Properties\Dynamic|array<Documents\Connectors\Properties\Dynamic> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setValidState(
		Documents\Connectors\Properties\Dynamic|array $property,
		bool $state,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->set(
					$item,
					Utils\ArrayHash::from([
						States\Property::VALID_FIELD => $state,
					]),
					$source,
				);
			}
		} else {
			$this->set(
				$property,
				Utils\ArrayHash::from([
					States\Property::VALID_FIELD => $state,
				]),
				$source,
			);
		}
	}

	/**
	 * @param Documents\Connectors\Properties\Dynamic|array<Documents\Connectors\Properties\Dynamic> $property
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setPendingState(
		Documents\Connectors\Properties\Dynamic|array $property,
		bool $pending,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				if ($pending === false) {
					$this->set(
						$item,
						Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => null,
							States\Property::PENDING_FIELD => false,
						]),
						$source,
					);
				} else {
					$this->set(
						$item,
						Utils\ArrayHash::from([
							States\Property::PENDING_FIELD => $this->clock->getNow()->format(
								DateTimeInterface::ATOM,
							),
						]),
						$source,
					);
				}
			}
		} else {
			if ($pending === false) {
				$this->set(
					$property,
					Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]),
					$source,
				);
			} else {
				$this->set(
					$property,
					Utils\ArrayHash::from([
						States\Property::PENDING_FIELD => $this->clock->getNow()->format(
							DateTimeInterface::ATOM,
						),
					]),
					$source,
				);
			}
		}
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		try {
			$result = $this->connectorPropertiesStatesManager->delete($id);

			if ($result) {
				$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityDeleted(
					$id,
					MetadataTypes\Sources\Module::DEVICES,
				));
			}

			return $result;
		} catch (Exceptions\InvalidState $ex) {
			$this->logger->error(
				'Connector state could not be deleted',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-properties-states',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-properties-states',
				],
			);
		}

		return false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function readState(
		Documents\Connectors\Properties\Dynamic $property,
	): Documents\States\Connectors\Properties\Property|null
	{
		try {
			$state = $this->connectorPropertyStateRepository->find($property->getId());

		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'connectors states repository is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-properties-states',
				],
			);

			return null;
		}

		try {
			if ($state === null) {
				return null;
			}

			$readValue = $this->convertStoredState($property, null, $state, true);
			$getValue = $this->convertStoredState($property, null, $state, false);

			return $this->documentFactory->create(
				Documents\States\Connectors\Properties\Property::class,
				[
					'id' => $property->getId()->toString(),
					'connector' => $property->getConnector()->toString(),
					'read' => $readValue->toArray(),
					'get' => $getValue->toArray(),
					'valid' => $state->isValid(),
					'pending' => $state->getPending() instanceof DateTimeInterface
						? $state->getPending()->format(DateTimeInterface::ATOM)
						: $state->getPending(),
					'created_at' => $readValue->getCreatedAt()?->format(DateTimeInterface::ATOM),
					'updated_at' => $readValue->getUpdatedAt()?->format(DateTimeInterface::ATOM),
				],
			);
		} catch (Exceptions\InvalidActualValue $ex) {
			try {
				$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]));

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (Exceptions\InvalidState $ex) {
				$this->logger->error(
					'Connector state could not be saved',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return null;
			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'connectors states manager is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
					],
				);

				return null;
			}
		} catch (Exceptions\InvalidExpectedValue $ex) {
			try {
				$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (Exceptions\InvalidState $ex) {
				$this->logger->error(
					'Connector state could not be saved',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);

				return null;
			} catch (Exceptions\NotImplemented) {
				$this->logger->warning(
					'connectors states manager is not configured. State could not be fetched',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
					],
				);

				return null;
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function writeState(
		Documents\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		bool $forWriting,
		MetadataTypes\Sources\Source|null $source,
	): void
	{
		try {
			$state = $this->connectorPropertyStateRepository->find($property->getId());
		} catch (Exceptions\NotImplemented) {
			$state = null;
		}

		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			try {
				if (
					$property->getInvalid() !== null
					&& strval(
						ToolsUtilities\Value::flattenValue(
							// @phpstan-ignore-next-line
							$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						),
					) === strval(
						ToolsUtilities\Value::flattenValue($property->getInvalid()),
					)
				) {
					$data->offsetSet(States\Property::ACTUAL_VALUE_FIELD, null);
					$data->offsetSet(States\Property::VALID_FIELD, false);

				} else {
					$actualValue = $this->convertWriteActualValue(
						// @phpstan-ignore-next-line
						$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						$property,
					);

					$data->offsetSet(
						States\Property::ACTUAL_VALUE_FIELD,
						ToolsUtilities\Value::flattenValue($actualValue),
					);
					$data->offsetSet(States\Property::VALID_FIELD, true);
				}
			} catch (ToolsExceptions\InvalidValue $ex) {
				$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connector-properties-states',
						'exception' => ToolsHelpers\Logger::buildException($ex),
					],
				);
			}
		}

		if ($data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)) {
			if (
				$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) !== null
				&& $data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) !== ''
			) {
				try {
					$expectedValue = $this->convertWriteExpectedValue(
						// @phpstan-ignore-next-line
						$data->offsetGet(States\Property::EXPECTED_VALUE_FIELD),
						$property,
						null,
						$forWriting,
					);

					if ($expectedValue !== null && !$property->isSettable()) {
						throw new Exceptions\InvalidArgument(
							'Property is not settable, expected value could not written',
						);
					}

					$data->offsetSet(
						States\Property::EXPECTED_VALUE_FIELD,
						ToolsUtilities\Value::flattenValue($expectedValue),
					);
					$data->offsetSet(
						States\Property::PENDING_FIELD,
						$expectedValue !== null,
					);
				} catch (ToolsExceptions\InvalidValue $ex) {
					$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
					$data->offsetSet(States\Property::PENDING_FIELD, false);

					$this->logger->error(
						'Provided property expected value was not valid',
						[
							'source' => MetadataTypes\Sources\Module::DEVICES->value,
							'type' => 'connector-properties-states',
							'exception' => ToolsHelpers\Logger::buildException($ex),
						],
					);
				}
			} else {
				$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
				$data->offsetSet(States\Property::PENDING_FIELD, false);
			}
		}

		try {
			if ($state !== null) {
				$actualValue = ToolsUtilities\Value::flattenValue(
					$this->convertReadValue($state->getActualValue(), $property, null, true),
				);
				$expectedValue = ToolsUtilities\Value::flattenValue(
					$this->convertWriteExpectedValue($state->getExpectedValue(), $property, null, false),
				);

				if (
					$data->offsetExists(States\Property::EXPECTED_VALUE_FIELD)
					&& $data->offsetGet(States\Property::EXPECTED_VALUE_FIELD) === $actualValue
				) {
					// If the new expected value is same as actual value
					// then the expected filed could be reset
					if ($expectedValue !== null) {
						// Expected value is set in the database
						// so it have to be cleared
						$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
						$data->offsetSet(States\Property::PENDING_FIELD, false);
					} else {
						// Expected value is not present
						// si it could be omitted
						$data->offsetUnset(States\Property::EXPECTED_VALUE_FIELD);
						$data->offsetUnset(States\Property::PENDING_FIELD);
					}
				}

				if (
					$data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)
					&& $data->offsetGet(States\Property::ACTUAL_VALUE_FIELD) === $expectedValue
				) {
					// If the new actual value is same as expected value
					// then the expected field could be reset
					$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
					$data->offsetSet(States\Property::PENDING_FIELD, false);
				}
			}
		} catch (ToolsExceptions\InvalidValue) {
			// Could be ignored
		}

		if ($data->count() === 0) {
			return;
		}

		try {
			if ($state === null) {
				$result = $this->connectorPropertiesStatesManager->create(
					$property,
					$data,
				);

			} else {
				$result = $this->connectorPropertiesStatesManager->update(
					$property,
					$state,
					$data,
				);

				if ($result === false) {
					return;
				}
			}

			$readValue = $this->convertStoredState($property, null, $result, true);
			$getValue = $this->convertStoredState($property, null, $result, false);

			if ($state === null) {
				$this->dispatcher?->dispatch(
					new Events\ConnectorPropertyStateEntityCreated(
						$property,
						$readValue,
						$getValue,
						$source ?? MetadataTypes\Sources\Module::DEVICES,
					),
				);
			} else {
				$this->dispatcher?->dispatch(
					new Events\ConnectorPropertyStateEntityUpdated(
						$property,
						$readValue,
						$getValue,
						$source ?? MetadataTypes\Sources\Module::DEVICES,
					),
				);
			}

			$this->logger->debug(
				$state === null ? 'Connector property state was created' : 'Connector property state was updated',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-properties-states',
					'property' => [
						'id' => $property->getId()->toString(),
						'state' => $result->toArray(),
					],
				],
			);
		} catch (Exceptions\InvalidState $ex) {
			$this->logger->error(
				'Connector state could not be saved',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-properties-states',
					'exception' => ToolsHelpers\Logger::buildException($ex),
				],
			);
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be saved',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'connector-properties-states',
				],
			);
		}
	}

}
