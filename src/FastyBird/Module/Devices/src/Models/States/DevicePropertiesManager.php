<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States;

use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching as DevicesCaching;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette\Caching as NetteCaching;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher as PsrEventDispatcher;
use Ramsey\Uuid;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function is_array;
use function strval;

/**
 * Useful device dynamic property state helpers
 *
 * @extends PropertiesManager<DevicesDocuments\Devices\Properties\Dynamic, DevicesDocuments\Devices\Properties\Mapped | null, States\DeviceProperty>
 */
final class DevicePropertiesManager extends PropertiesManager
{

	public function __construct(
		private readonly bool $useExchange,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\States\Devices\Repository $devicePropertyStateRepository,
		private readonly Models\States\Devices\Manager $devicePropertiesStatesManager,
		private readonly DevicesCaching\Container $moduleCaching,
		private readonly ClockInterface $clock,
		private readonly CoreDocuments\DocumentFactory $documentFactory,
		private readonly Publisher\MessagePublisher $publisher,
		Devices\Logger $logger,
		CoreDocuments\Mapping\Mapper $stateMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct($logger, $stateMapper);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\Logic
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function read(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Devices\Properties\Mapped $property,
		Sources\Source|null $source,
	): bool|DevicesDocuments\States\Devices\Properties\Property|null
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? Sources\Module::DEVICES,
					Types\RoutingKey::DEVICE_PROPERTY_ACTION->value,
					$this->documentFactory->create(
						DevicesDocuments\States\Devices\Properties\Actions\Action::class,
						[
							'action' => Types\PropertyAction::GET->value,
							'device' => $property->getDevice()->toString(),
							'property' => $property->getId()->toString(),
						],
					),
				);
			} catch (Throwable $ex) {
				throw new DevicesExceptions\InvalidState(
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
					NetteCaching\Cache::Tags => array_merge(
						[$property->getId()->toString()],
						$property instanceof DevicesDocuments\Devices\Properties\Mapped
							? [$property->getParent()->toString()]
							: [],
					),
				],
			);
			assert($document instanceof DevicesDocuments\States\Devices\Properties\Property || $document === null);

			return $document;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function write(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Devices\Properties\Mapped $property,
		States\PropertyUpdate $data,
		Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? Sources\Module::DEVICES,
					Types\RoutingKey::DEVICE_PROPERTY_ACTION->value,
					$this->documentFactory->create(
						DevicesDocuments\States\Devices\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'device' => $property->getDevice()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'write' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|Payloads\Payload|null $item): bool|int|float|string|null => Utilities\Value::flattenValue(
										$item,
									),
									$data->toArray(),
								),
							],
						),
					),
				);
			} catch (Throwable $ex) {
				throw new DevicesExceptions\InvalidState(
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function set(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Devices\Properties\Mapped $property,
		States\PropertyUpdate $data,
		Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? Sources\Module::DEVICES,
					Types\RoutingKey::DEVICE_PROPERTY_ACTION->value,
					$this->documentFactory->create(
						DevicesDocuments\States\Devices\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'device' => $property->getDevice()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'set' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|Payloads\Payload|null $item): bool|int|float|string|null => Utilities\Value::flattenValue(
										$item,
									),
									$data->toArray(),
								),
							],
						),
					),
				);
			} catch (Throwable $ex) {
				throw new DevicesExceptions\InvalidState(
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
	 * @param DevicesDocuments\Devices\Properties\Dynamic|array<DevicesDocuments\Devices\Properties\Dynamic> $property
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setValidState(
		DevicesDocuments\Devices\Properties\Dynamic|array $property,
		bool $state,
		Sources\Source|null $source,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				$this->set(
					$item,
					States\PropertyUpdate::create()->withValid($state),
					$source,
				);
			}
		} else {
			$this->set(
				$property,
				States\PropertyUpdate::create()->withValid($state),
				$source,
			);
		}
	}

	/**
	 * @param DevicesDocuments\Devices\Properties\Dynamic|array<DevicesDocuments\Devices\Properties\Dynamic> $property
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setPendingState(
		DevicesDocuments\Devices\Properties\Dynamic|array $property,
		bool $pending,
		Sources\Source|null $source,
	): void
	{
		if (is_array($property)) {
			foreach ($property as $item) {
				if ($pending === false) {
					$this->set(
						$item,
						States\PropertyUpdate::create()->withExpectedValue(null)->withPending(false),
						$source,
					);
				} else {
					$this->set(
						$item,
						States\PropertyUpdate::create()->withPending($this->clock->now()),
						$source,
					);
				}
			}
		} else {
			if ($pending === false) {
				$this->set(
					$property,
					States\PropertyUpdate::create()->withExpectedValue(null)->withPending(false),
					$source,
				);
			} else {
				$this->set(
					$property,
					States\PropertyUpdate::create()->withPending($this->clock->now()),
					$source,
				);
			}
		}
	}

	public function delete(Uuid\UuidInterface $id): bool
	{
		try {
			$result = $this->devicePropertiesStatesManager->delete($id);

			if ($result) {
				$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted(
					$id,
					Sources\Module::DEVICES,
				));

				foreach ($this->findChildren($id) as $child) {
					$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted(
						$child->getId(),
						Sources\Module::DEVICES,
					));
				}
			}

			return $result;
		} catch (DevicesExceptions\InvalidState $ex) {
			$this->logger->error(
				'Device state could not be deleted',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'device-properties-states',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		} catch (DevicesExceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'device-properties-states',
				],
			);
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function readState(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Devices\Properties\Mapped $property,
	): DevicesDocuments\States\Devices\Properties\Property|null
	{
		$mappedProperty = null;

		if ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				throw new DevicesExceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = $this->devicePropertyStateRepository->find($property->getId());

		} catch (DevicesExceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states repository is not configured. State could not be fetched',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'device-properties-states',
				],
			);

			return null;
		}

		try {
			if ($state === null) {
				return null;
			}

			$readValue = $this->convertStoredState($property, $mappedProperty, $state, true);
			$getValue = $this->convertStoredState($property, $mappedProperty, $state, false);

			return $this->documentFactory->create(
				DevicesDocuments\States\Devices\Properties\Property::class,
				[
					'id' => $property->getId()->toString(),
					'device' => $property->getDevice()->toString(),
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
		} catch (DevicesExceptions\InvalidActualValue $ex) {
			try {
				$this->devicePropertiesStatesManager->update($property, $state, [
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]);

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (DevicesExceptions\InvalidState $ex) {
				$this->logger->error(
					'Device state could not be saved',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return null;
			} catch (DevicesExceptions\NotImplemented) {
				$this->logger->warning(
					'Devices states manager is not configured. State could not be fetched',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
					],
				);

				return null;
			}
		} catch (DevicesExceptions\InvalidExpectedValue $ex) {
			try {
				$this->devicePropertiesStatesManager->update($property, $state, [
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]);

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (DevicesExceptions\InvalidState $ex) {
				$this->logger->error(
					'Device state could not be saved',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return null;
			} catch (DevicesExceptions\NotImplemented) {
				$this->logger->warning(
					'Devices states manager is not configured. State could not be fetched',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
					],
				);

				return null;
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function writeState(
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Devices\Properties\Mapped $property,
		States\PropertyUpdate $data,
		bool $forWriting,
		Sources\Source|null $source,
	): void
	{
		$values = $data->toArray();

		$mappedProperty = null;

		if ($property instanceof DevicesDocuments\Devices\Properties\Mapped) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				throw new DevicesExceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		if ($mappedProperty !== null && $forWriting === false) {
			throw new DevicesExceptions\InvalidArgument('Mapped property could not be stored as from device');
		}

		try {
			$state = $this->devicePropertyStateRepository->find($property->getId());
		} catch (DevicesExceptions\NotImplemented) {
			$state = null;
		}

		if (array_key_exists(States\Property::ACTUAL_VALUE_FIELD, $values)) {
			try {
				if (
					$property->getInvalid() !== null
					&& strval(
						Utilities\Value::flattenValue(
							$values[States\Property::ACTUAL_VALUE_FIELD],
						),
					) === strval(
						Utilities\Value::flattenValue($property->getInvalid()),
					)
				) {
					$values[States\Property::ACTUAL_VALUE_FIELD] = null;
					$values[States\Property::VALID_FIELD] = false;

				} else {
					$actualValue = $this->convertWriteActualValue(
						$values[States\Property::ACTUAL_VALUE_FIELD],
						$property,
					);

					$values[States\Property::ACTUAL_VALUE_FIELD] = Utilities\Value::flattenValue($actualValue);
					$values[States\Property::VALID_FIELD] = true;
				}
			} catch (ValuesExceptions\InvalidValue $ex) {
				unset($values[States\Property::ACTUAL_VALUE_FIELD]);
				$values[States\Property::VALID_FIELD] = false;

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'device-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);
			}
		}

		if (array_key_exists(States\Property::EXPECTED_VALUE_FIELD, $values)) {
			if (
				$values[States\Property::EXPECTED_VALUE_FIELD] !== null
				&& $values[States\Property::EXPECTED_VALUE_FIELD] !== ''
			) {
				try {
					$expectedValue = $this->convertWriteExpectedValue(
						$values[States\Property::EXPECTED_VALUE_FIELD],
						$property,
						$mappedProperty,
						$forWriting,
					);

					if (
						$expectedValue !== null
						&& (
							!$property->isSettable()
							|| (
								$mappedProperty !== null
								&& !$mappedProperty->isSettable()
							)
						)
					) {
						throw new DevicesExceptions\InvalidArgument(
							'Property is not settable, expected value could not written',
						);
					}

					$values[States\Property::EXPECTED_VALUE_FIELD] = Utilities\Value::flattenValue($expectedValue);
					$values[States\Property::PENDING_FIELD] = $expectedValue !== null;
				} catch (ValuesExceptions\InvalidValue $ex) {
					$values[States\Property::EXPECTED_VALUE_FIELD] = null;
					$values[States\Property::PENDING_FIELD] = false;

					$this->logger->error(
						'Provided property expected value was not valid',
						[
							'source' => Sources\Module::DEVICES->value,
							'type' => 'device-properties-states',
							'exception' => Logging\Logger::buildException($ex),
						],
					);
				}
			} else {
				$values[States\Property::EXPECTED_VALUE_FIELD] = null;
				$values[States\Property::PENDING_FIELD] = false;
			}
		}

		try {
			if ($state !== null) {
				$actualValue = Utilities\Value::flattenValue(
					$this->convertReadValue($state->getActualValue(), $property, null, true),
				);
				$expectedValue = Utilities\Value::flattenValue(
					$this->convertWriteExpectedValue($state->getExpectedValue(), $property, null, false),
				);

				if (
					array_key_exists(States\Property::EXPECTED_VALUE_FIELD, $values)
					&& $values[States\Property::EXPECTED_VALUE_FIELD] === $actualValue
				) {
					// If the new expected value is same as actual value
					// then the expected filed could be reset
					if ($expectedValue !== null) {
						// Expected value is set in the database
						// so it have to be cleared
						$values[States\Property::EXPECTED_VALUE_FIELD] = null;
						$values[States\Property::PENDING_FIELD] = false;
					} else {
						// Expected value is not present
						// si it could be omitted
						unset($values[States\Property::EXPECTED_VALUE_FIELD]);
						unset($values[States\Property::PENDING_FIELD]);
					}
				}

				if (
					array_key_exists(States\Property::ACTUAL_VALUE_FIELD, $values)
					&& $values[States\Property::ACTUAL_VALUE_FIELD] === $expectedValue
				) {
					// If the new actual value is same as expected value
					// then the expected field could be reset
					$values[States\Property::EXPECTED_VALUE_FIELD] = null;
					$values[States\Property::PENDING_FIELD] = false;
				}
			}
		} catch (ValuesExceptions\InvalidValue) {
			// Could be ignored
		}

		if ($values === []) {
			return;
		}

		try {
			if ($state === null) {
				$result = $this->devicePropertiesStatesManager->create(
					$property,
					$values,
				);

			} else {
				$result = $this->devicePropertiesStatesManager->update(
					$property,
					$state,
					$values,
				);

				if ($result === false) {
					return;
				}
			}

			$readValue = $this->convertStoredState($property, null, $result, true);
			$getValue = $this->convertStoredState($property, null, $result, false);

			if ($state === null) {
				$this->dispatcher?->dispatch(
					new Events\DevicePropertyStateEntityCreated(
						$property,
						$readValue,
						$getValue,
						$source ?? Sources\Module::DEVICES,
					),
				);
			} else {
				$this->dispatcher?->dispatch(
					new Events\DevicePropertyStateEntityUpdated(
						$property,
						$readValue,
						$getValue,
						$source ?? Sources\Module::DEVICES,
					),
				);
			}

			foreach ($this->findChildren($property->getId()) as $child) {
				$readValue = $this->convertStoredState($property, $child, $result, true);
				$getValue = $this->convertStoredState($property, $child, $result, false);

				if ($state === null) {
					$this->dispatcher?->dispatch(
						new Events\DevicePropertyStateEntityCreated(
							$child,
							$readValue,
							$getValue,
							$source ?? Sources\Module::DEVICES,
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new Events\DevicePropertyStateEntityUpdated(
							$child,
							$readValue,
							$getValue,
							$source ?? Sources\Module::DEVICES,
						),
					);
				}
			}

			$this->logger->debug(
				$state === null ? 'Device property state was created' : 'Device property state was updated',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'device-properties-states',
					'property' => [
						'id' => $property->getId()->toString(),
						'state' => $result->toArray(),
					],
				],
			);
		} catch (DevicesExceptions\InvalidState $ex) {
			$this->logger->error(
				'Device state could not be saved',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'device-properties-states',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		} catch (DevicesExceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be saved',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'device-properties-states',
				],
			);
		}
	}

	/**
	 * @return array<DevicesDocuments\Devices\Properties\Mapped>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findChildren(Uuid\UuidInterface $id): array
	{
		$findPropertiesQuery = new Queries\Configuration\FindDeviceMappedProperties();
		$findPropertiesQuery->byParentId($id);

		return $this->devicePropertiesConfigurationRepository->findAllBy(
			$findPropertiesQuery,
			DevicesDocuments\Devices\Properties\Mapped::class,
		);
	}

}
