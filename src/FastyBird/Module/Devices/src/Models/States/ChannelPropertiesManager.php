<?php declare(strict_types = 1);

/**
 * ChannelPropertiesStates.php
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
use FastyBird\Core\Clock;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Caching;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
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
use function boolval;
use function is_array;
use function strval;

/**
 * Useful channel dynamic property state helpers
 *
 * @extends PropertiesManager<DevicesDocuments\Channels\Properties\Dynamic, DevicesDocuments\Channels\Properties\Mapped | null, States\ChannelProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertiesManager extends PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\Channels\Repository $channelPropertyStateRepository,
		private readonly Models\States\Channels\Manager $channelPropertiesStatesManager,
		private readonly Caching\Container $moduleCaching,
		private readonly Clock\Clock $clock,
		private readonly CoreDocuments\DocumentFactory $documentFactory,
		private readonly Publisher\MessagePublisher $publisher,
		Devices\Logger $logger,
		ObjectMapper\Processing\Processor $stateMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct($logger, $stateMapper);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function read(
		DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
		Sources\Source|null $source,
	): bool|DevicesDocuments\States\Channels\Properties\Property|null
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						DevicesDocuments\States\Channels\Properties\Actions\Action::class,
						[
							'action' => Types\PropertyAction::GET->value,
							'channel' => $property->getChannel()->toString(),
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
						$property instanceof DevicesDocuments\Channels\Properties\Mapped
							? [$property->getParent()->toString()]
							: [],
					),
				],
			);
			assert($document instanceof DevicesDocuments\States\Channels\Properties\Property || $document === null);

			return $document;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function write(
		DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
		Utils\ArrayHash $data,
		Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						DevicesDocuments\States\Channels\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'channel' => $property->getChannel()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'write' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|Payloads\Payload|null $item): bool|int|float|string|null => Utilities\Value::flattenValue(
										$item,
									),
									(array) $data,
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function set(
		DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
		Utils\ArrayHash $data,
		Sources\Source|null $source,
	): void
	{
		if ($this->useExchange) {
			try {
				$this->publisher->publish(
					$source ?? Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						DevicesDocuments\States\Channels\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'channel' => $property->getChannel()->toString(),
								'property' => $property->getId()->toString(),
							],
							[
								'set' => array_map(
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									static fn (bool|int|float|string|DateTimeInterface|Payloads\Payload|null $item): bool|int|float|string|null => Utilities\Value::flattenValue(
										$item,
									),
									(array) $data,
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
	 * @param DevicesDocuments\Channels\Properties\Dynamic|array<DevicesDocuments\Channels\Properties\Dynamic> $property
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setValidState(
		DevicesDocuments\Channels\Properties\Dynamic|array $property,
		bool $state,
		Sources\Source|null $source,
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
	 * @param DevicesDocuments\Channels\Properties\Dynamic|array<DevicesDocuments\Channels\Properties\Dynamic> $property
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setPendingState(
		DevicesDocuments\Channels\Properties\Dynamic|array $property,
		bool $pending,
		Sources\Source|null $source,
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
			$result = $this->channelPropertiesStatesManager->delete($id);

			if ($result) {
				$this->dispatcher?->dispatch(new Events\ChannelPropertyStateEntityDeleted(
					$id,
					Sources\Module::DEVICES,
				));

				foreach ($this->findChildren($id) as $child) {
					$this->dispatcher?->dispatch(new Events\ChannelPropertyStateEntityDeleted(
						$child->getId(),
						Sources\Module::DEVICES,
					));
				}
			}

			return $result;
		} catch (DevicesExceptions\InvalidState $ex) {
			$this->logger->error(
				'Channel state could not be deleted',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channel-properties-states',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		} catch (DevicesExceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states manager is not configured. State could not be fetched',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channel-properties-states',
				],
			);
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function readState(
		DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
	): DevicesDocuments\States\Channels\Properties\Property|null
	{
		$mappedProperty = null;

		if ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				throw new DevicesExceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = $this->channelPropertyStateRepository->find($property->getId());

		} catch (DevicesExceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states repository is not configured. State could not be fetched',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channel-properties-states',
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
				DevicesDocuments\States\Channels\Properties\Property::class,
				[
					'id' => $property->getId()->toString(),
					'channel' => $property->getChannel()->toString(),
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
				$this->channelPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::ACTUAL_VALUE_FIELD => null,
					States\Property::VALID_FIELD => false,
				]));

				$this->logger->error(
					'Property stored actual value was not valid',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (DevicesExceptions\InvalidState $ex) {
				$this->logger->error(
					'Channel state could not be saved',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return null;
			} catch (DevicesExceptions\NotImplemented) {
				$this->logger->warning(
					'Channels states manager is not configured. State could not be fetched',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
					],
				);

				return null;
			}
		} catch (DevicesExceptions\InvalidExpectedValue $ex) {
			try {
				$this->channelPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => null,
					States\Property::PENDING_FIELD => false,
				]));

				$this->logger->error(
					'Property stored expected value was not valid',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return $this->readState($property);
			} catch (DevicesExceptions\InvalidState $ex) {
				$this->logger->error(
					'Channel state could not be saved',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				return null;
			} catch (DevicesExceptions\NotImplemented) {
				$this->logger->warning(
					'Channels states manager is not configured. State could not be fetched',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
					],
				);

				return null;
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function writeState(
		DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
		Sources\Source|null $source,
	): void
	{
		$mappedProperty = null;

		if ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			$parent = $this->channelPropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				throw new DevicesExceptions\InvalidState('Mapped property parent could not be loaded');
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		try {
			$state = $this->channelPropertyStateRepository->find($property->getId());
		} catch (DevicesExceptions\NotImplemented) {
			$state = null;
		}

		/**
		 * IMPORTANT: ACTUAL VALUE field is meant to be used only by connectors for saving device actual value
		 */
		if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
			if ($mappedProperty !== null) {
				throw new DevicesExceptions\InvalidArgument(
					'Setting property actual value is not allowed for mapped properties',
				);
			}

			if ($forWriting === true) {
				throw new DevicesExceptions\InvalidArgument(
					'Setting property actual value could be done only by "setValue" method',
				);
			}

			try {
				if (
					$property->getInvalid() !== null
					&& strval(
						Utilities\Value::flattenValue(
							// @phpstan-ignore-next-line
							$data->offsetGet(States\Property::ACTUAL_VALUE_FIELD),
						),
					) === strval(
						Utilities\Value::flattenValue($property->getInvalid()),
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
						Utilities\Value::flattenValue($actualValue),
					);

					if ($data->offsetExists(States\Property::VALID_FIELD)) {
						$data->offsetSet(
							States\Property::VALID_FIELD,
							boolval($data->offsetGet(States\Property::VALID_FIELD)),
						);
					} else {
						$data->offsetSet(States\Property::VALID_FIELD, true);
					}
				}
			} catch (ValuesExceptions\InvalidValue $ex) {
				$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
				$data->offsetSet(States\Property::VALID_FIELD, false);

				$this->logger->error(
					'Provided property actual value is not valid',
					[
						'source' => Sources\Module::DEVICES->value,
						'type' => 'channel-properties-states',
						'exception' => Logging\Logger::buildException($ex),
					],
				);
			}
		}

		/**
		 * IMPORTANT: EXPECTED VALUE field is meant to be used mainly by user interface for saving value which should
		 * be then written into device
		 */
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

					$data->offsetSet(
						States\Property::EXPECTED_VALUE_FIELD,
						Utilities\Value::flattenValue($expectedValue),
					);
					$data->offsetSet(
						States\Property::PENDING_FIELD,
						$expectedValue !== null,
					);
				} catch (ValuesExceptions\InvalidValue $ex) {
					$data->offsetSet(States\Property::EXPECTED_VALUE_FIELD, null);
					$data->offsetSet(States\Property::PENDING_FIELD, false);

					$this->logger->error(
						'Provided property expected value was not valid',
						[
							'source' => Sources\Module::DEVICES->value,
							'type' => 'channel-properties-states',
							'exception' => Logging\Logger::buildException($ex),
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
				$actualValue = Utilities\Value::flattenValue(
					$this->convertReadValue($state->getActualValue(), $property, null, true),
				);
				$expectedValue = Utilities\Value::flattenValue(
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
		} catch (ValuesExceptions\InvalidValue) {
			// Could be ignored
		}

		if ($data->count() === 0) {
			return;
		}

		try {
			if ($state === null) {
				$result = $this->channelPropertiesStatesManager->create(
					$property,
					$data,
				);

			} else {
				$result = $this->channelPropertiesStatesManager->update(
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
					new Events\ChannelPropertyStateEntityCreated(
						$property,
						$readValue,
						$getValue,
						$source ?? Sources\Module::DEVICES,
					),
				);
			} else {
				$this->dispatcher?->dispatch(
					new Events\ChannelPropertyStateEntityUpdated(
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
						new Events\ChannelPropertyStateEntityCreated(
							$child,
							$readValue,
							$getValue,
							$source ?? Sources\Module::DEVICES,
						),
					);
				} else {
					$this->dispatcher?->dispatch(
						new Events\ChannelPropertyStateEntityUpdated(
							$child,
							$readValue,
							$getValue,
							$source ?? Sources\Module::DEVICES,
						),
					);
				}
			}

			$this->logger->debug(
				$state === null ? 'Channel property state was created' : 'Channel property state was updated',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channel-properties-states',
					'property' => [
						'id' => $property->getId()->toString(),
						'state' => $result->toArray(),
					],
				],
			);
		} catch (DevicesExceptions\InvalidState $ex) {
			$this->logger->error(
				'Channel state could not be saved',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channel-properties-states',
					'exception' => Logging\Logger::buildException($ex),
				],
			);
		} catch (DevicesExceptions\NotImplemented) {
			$this->logger->warning(
				'Channels states manager is not configured. State could not be saved',
				[
					'source' => Sources\Module::DEVICES->value,
					'type' => 'channel-properties-states',
				],
			);
		}
	}

	/**
	 * @return array<DevicesDocuments\Channels\Properties\Mapped>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findChildren(Uuid\UuidInterface $id): array
	{
		$findPropertiesQuery = new Queries\Configuration\FindChannelMappedProperties();
		$findPropertiesQuery->byParentId($id);

		return $this->channelPropertiesConfigurationRepository->findAllBy(
			$findPropertiesQuery,
			DevicesDocuments\Channels\Properties\Mapped::class,
		);
	}

}
