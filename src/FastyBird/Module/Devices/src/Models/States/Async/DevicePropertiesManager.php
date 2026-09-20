<?php declare(strict_types = 1);

/**
 * DevicePropertiesStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           22.01.24
 */

namespace FastyBird\Module\Devices\Models\States\Async;

use DateTimeInterface;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
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
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette;
use Nette\Caching as NetteCaching;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\EventDispatcher as PsrEventDispatcher;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function array_map;
use function array_merge;
use function boolval;
use function is_array;
use function is_bool;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Useful device dynamic property state helpers
 *
 * @extends Models\States\PropertiesManager<Documents\Devices\Properties\Dynamic, Documents\Devices\Properties\Mapped | null, States\DeviceProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertiesManager extends Models\States\PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\States\Devices\Async\Repository $devicePropertyStateRepository,
		private readonly Models\States\Devices\Async\Manager $devicePropertiesStatesManager,
		private readonly Caching\Container $moduleCaching,
		private readonly DateTimeFactory\Clock $clock,
		private readonly ApplicationDocuments\DocumentFactory $documentFactory,
		private readonly ExchangePublisher\Async\Publisher $publisher,
		Devices\Logger $logger,
		ObjectMapper\Processing\Processor $stateMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		parent::__construct($logger, $stateMapper);
	}

	/**
	 * @return Promise\PromiseInterface<bool|Documents\States\Devices\Properties\Property|null>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function read(
		Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped $property,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						Documents\States\Devices\Properties\Actions\Action::class,
						[
							'action' => Types\PropertyAction::GET->value,
							'device' => $property->getDevice()->toString(),
							'property' => $property->getId()->toString(),
						],
					),
				);
			} catch (Throwable $ex) {
				return Promise\reject(new Exceptions\InvalidState(
					'Requested action could not be published for write action',
					$ex->getCode(),
					$ex,
				));
			}
		} else {
			/** @phpstan-var Documents\States\Devices\Properties\Property|null $document */
			$document = $this->moduleCaching->getStateCache()->load('read_' . $property->getId()->toString());

			if ($document !== null) {
				return Promise\resolve($document);
			}

			$deferred = new Promise\Deferred();

			$this->readState($property)
				->then(
					function (Documents\States\Devices\Properties\Property|null $document) use ($deferred, $property): void {
						$this->moduleCaching->getStateCache()->save(
							'read_' . $property->getId()->toString(),
							$document,
							[
								NetteCaching\Cache::Tags => array_merge(
									[$property->getId()->toString()],
									$property instanceof Documents\Devices\Properties\Mapped
										? [$property->getParent()->toString()]
										: [],
								),
							],
						);

						$deferred->resolve($document);
					},
				)
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function write(
		Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						Documents\States\Devices\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'device' => $property->getDevice()->toString(),
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
				return Promise\reject(new Exceptions\InvalidState(
					'Requested value could not be published for write action',
					$ex->getCode(),
					$ex,
				));
			}
		} else {
			return $this->writeState($property, $data, true, $source);
		}
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function set(
		Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
					$source ?? MetadataTypes\Sources\Module::DEVICES,
					Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY,
					$this->documentFactory->create(
						Documents\States\Devices\Properties\Actions\Action::class,
						array_merge(
							[
								'action' => Types\PropertyAction::SET->value,
								'device' => $property->getDevice()->toString(),
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
				return Promise\reject(new Exceptions\InvalidState(
					'Requested value could not be published for set action',
					$ex->getCode(),
					$ex,
				));
			}
		} else {
			return $this->writeState($property, $data, false, $source);
		}
	}

	/**
	 * @param Documents\Devices\Properties\Dynamic|array<Documents\Devices\Properties\Dynamic> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function setValidState(
		Documents\Devices\Properties\Dynamic|array $property,
		bool $state,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if (is_array($property)) {
			$deferred = new Promise\Deferred();

			$promises = [];

			foreach ($property as $item) {
				$promises[] = $this->set(
					$item,
					Utils\ArrayHash::from([
						States\Property::VALID_FIELD => $state,
					]),
					$source,
				);
			}

			Promise\all($promises)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::VALID_FIELD => $state,
			]),
			$source,
		);
	}

	/**
	 * @param Documents\Devices\Properties\Dynamic|array<Documents\Devices\Properties\Dynamic> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function setPendingState(
		Documents\Devices\Properties\Dynamic|array $property,
		bool $pending,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if (is_array($property)) {
			$deferred = new Promise\Deferred();

			$promises = [];

			foreach ($property as $item) {
				$promises[] = $pending === false ? $this->set(
					$item,
					Utils\ArrayHash::from([
						States\Property::EXPECTED_VALUE_FIELD => null,
						States\Property::PENDING_FIELD => false,
					]),
					$source,
				) : $this->set(
					$item,
					Utils\ArrayHash::from([
						States\Property::PENDING_FIELD => $this->clock->getNow()->format(
							DateTimeInterface::ATOM,
						),
					]),
					$source,
				);
			}

			Promise\all($promises)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $pending === false ? $this->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::EXPECTED_VALUE_FIELD => null,
				States\Property::PENDING_FIELD => false,
			]),
			$source,
		) : $this->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::PENDING_FIELD => $this->clock->getNow()->format(DateTimeInterface::ATOM),
			]),
			$source,
		);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		try {
			$deferred = new Promise\Deferred();

			$this->devicePropertiesStatesManager->delete($id)
				->then(function (bool $result) use ($deferred, $id): void {
					$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted(
						$id,
						MetadataTypes\Sources\Module::DEVICES,
					));

					foreach ($this->findChildren($id) as $child) {
						$this->dispatcher?->dispatch(new Events\DevicePropertyStateEntityDeleted(
							$child->getId(),
							MetadataTypes\Sources\Module::DEVICES,
						));
					}

					$deferred->resolve($result);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Devices states manager is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'async-device-properties-states',
				],
			);
		}

		return Promise\resolve(false);
	}

	/**
	 * @return Promise\PromiseInterface<Documents\States\Devices\Properties\Property|null>
	 *
	 * @throws Exceptions\InvalidState
	 *
	 * @interal
	 */
	public function readState(
		Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped $property,
	): Promise\PromiseInterface
	{
		$mappedProperty = null;

		if ($property instanceof Documents\Devices\Properties\Mapped) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof Documents\Devices\Properties\Dynamic) {
				return Promise\reject(new Exceptions\InvalidState('Mapped property parent could not be loaded'));
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		$deferred = new Promise\Deferred();

		$this->devicePropertyStateRepository->find($property->getId())
			->then(
				function (
					States\DeviceProperty|null $state,
				) use (
					$deferred,
					$property,
					$mappedProperty,
				): void {
					if ($state === null) {
						$deferred->resolve(null);

						return;
					}

					try {
						$readValue = $this->convertStoredState($property, $mappedProperty, $state, true);
						$getValue = $this->convertStoredState($property, $mappedProperty, $state, false);

						$deferred->resolve($this->documentFactory->create(
							Documents\States\Devices\Properties\Property::class,
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
						));
					} catch (Exceptions\InvalidActualValue $ex) {
						$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
							States\Property::ACTUAL_VALUE_FIELD => null,
							States\Property::VALID_FIELD => false,
						]))
							->then(function () use ($property, $deferred): void {
								$this->readState($property)
									->then(static function ($state) use ($deferred): void {
										$deferred->resolve($state);
									})
									->catch(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									});
							})
							->catch(function (Throwable $ex) use ($deferred): void {
								if ($ex instanceof Exceptions\NotImplemented) {
									$this->logger->warning(
										'Devices states manager is not configured. State could not be fetched',
										[
											'source' => MetadataTypes\Sources\Module::DEVICES->value,
											'type' => 'async-device-properties-states',
										],
									);
								}

								$deferred->reject($ex);
							});

						$this->logger->error(
							'Property stored actual value was not valid',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'async-device-properties-states',
								'exception' => ToolsHelpers\Logger::buildException($ex),
							],
						);
					} catch (Exceptions\InvalidExpectedValue $ex) {
						$this->devicePropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
							States\Property::EXPECTED_VALUE_FIELD => null,
							States\Property::PENDING_FIELD => false,
						]))
							->then(function () use ($property, $deferred): void {
								$this->readState($property)
									->then(static function ($state) use ($deferred): void {
										$deferred->resolve($state);
									})
									->catch(static function (Throwable $ex) use ($deferred): void {
										$deferred->reject($ex);
									});
							})
							->catch(function (Throwable $ex) use ($deferred): void {
								if ($ex instanceof Exceptions\NotImplemented) {
									$this->logger->warning(
										'Devices states manager is not configured. State could not be fetched',
										[
											'source' => MetadataTypes\Sources\Module::DEVICES->value,
											'type' => 'async-device-properties-states',
										],
									);
								}

								$deferred->reject($ex);
							});

						$this->logger->error(
							'Property stored expected value was not valid',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'async-device-properties-states',
								'exception' => ToolsHelpers\Logger::buildException($ex),
							],
						);
					}
				},
			)
			->catch(function (Throwable $ex) use ($deferred): void {
				if ($ex instanceof Exceptions\NotImplemented) {
					$this->logger->warning(
						'Devices states repository is not configured. State could not be fetched',
						[
							'source' => MetadataTypes\Sources\Module::DEVICES->value,
							'type' => 'async-device-properties-states',
						],
					);
				}

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @interal
	 */
	public function writeState(
		Documents\Devices\Properties\Dynamic|Documents\Devices\Properties\Mapped $property,
		Utils\ArrayHash $data,
		bool $forWriting,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		$mappedProperty = null;

		if ($property instanceof Documents\Devices\Properties\Mapped) {
			$parent = $this->devicePropertiesConfigurationRepository->find($property->getParent());

			if (!$parent instanceof Documents\Devices\Properties\Dynamic) {
				return Promise\reject(new Exceptions\InvalidState('Mapped property parent could not be loaded'));
			}

			$mappedProperty = $property;

			$property = $parent;
		}

		$deferred = new Promise\Deferred();

		$this->devicePropertyStateRepository->find($property->getId())
			->then(async(
				function (
					States\DeviceProperty|null $state,
				) use (
					$deferred,
					$data,
					$property,
					$mappedProperty,
					$forWriting,
					$source,
				): void {
					/**
					 * IMPORTANT: ACTUAL VALUE field is meant to be used only by connectors for saving device actual value
					 */
					if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
						if ($mappedProperty !== null) {
							$deferred->reject(new Exceptions\InvalidArgument(
								'Setting property actual value is not allowed for mapped properties',
							));

							return;
						}

						if ($forWriting === true) {
							$deferred->reject(new Exceptions\InvalidArgument(
								'Setting property actual value could be done only by "setValue" method',
							));

							return;
						}

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

								if ($data->offsetExists(States\Property::VALID_FIELD)) {
									$data->offsetSet(
										States\Property::VALID_FIELD,
										boolval($data->offsetGet(States\Property::VALID_FIELD)),
									);
								} else {
									$data->offsetSet(States\Property::VALID_FIELD, true);
								}
							}
						} catch (ToolsExceptions\InvalidValue $ex) {
							$data->offsetUnset(States\Property::ACTUAL_VALUE_FIELD);
							$data->offsetSet(States\Property::VALID_FIELD, false);

							$this->logger->error(
								'Provided property actual value is not valid',
								[
									'source' => MetadataTypes\Sources\Module::DEVICES->value,
									'type' => 'async-device-properties-states',
									'exception' => ToolsHelpers\Logger::buildException($ex),
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
									$deferred->reject(new Exceptions\InvalidArgument(
										'Property is not settable, expected value could not written',
									));

									return;
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
										'type' => 'async-device-properties-states',
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
						$deferred->resolve(true);

						return;
					}

					try {
						if ($state === null) {
							$result = await($this->devicePropertiesStatesManager->create(
								$property,
								$data,
							));

						} else {
							$result = await($this->devicePropertiesStatesManager->update(
								$property,
								$state,
								$data,
							));

							if (is_bool($result)) {
								$deferred->resolve(false);

								return;
							}
						}

						$this->moduleCaching->getStateCache()->clean([
							NetteCaching\Cache::Tags => [$property->getId()->toString()],
						]);

						$readValue = $this->convertStoredState($property, null, $result, true);
						$getValue = $this->convertStoredState($property, null, $result, false);

						if ($state === null) {
							$this->dispatcher?->dispatch(
								new Events\DevicePropertyStateEntityCreated(
									$property,
									$readValue,
									$getValue,
									$source ?? MetadataTypes\Sources\Module::DEVICES,
								),
							);
						} else {
							$this->dispatcher?->dispatch(
								new Events\DevicePropertyStateEntityUpdated(
									$property,
									$readValue,
									$getValue,
									$source ?? MetadataTypes\Sources\Module::DEVICES,
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
										$source ?? MetadataTypes\Sources\Module::DEVICES,
									),
								);
							} else {
								$this->dispatcher?->dispatch(
									new Events\DevicePropertyStateEntityUpdated(
										$child,
										$readValue,
										$getValue,
										$source ?? MetadataTypes\Sources\Module::DEVICES,
									),
								);
							}
						}

						$this->logger->debug(
							$state === null ? 'Device property state was created' : 'Device property state was updated',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'async-device-properties-states',
								'property' => [
									'id' => $property->getId()->toString(),
									'state' => $result->toArray(),
								],
							],
						);

						$deferred->resolve(true);
					} catch (Throwable $ex) {
						if ($ex instanceof Exceptions\NotImplemented) {
							$this->logger->warning(
								'Devices states manager is not configured. State could not be saved',
								[
									'source' => MetadataTypes\Sources\Module::DEVICES->value,
									'type' => 'async-device-properties-states',
								],
							);
						}

						$deferred->reject($ex);
					}
				},
			))
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return array<Documents\Devices\Properties\Mapped>
	 *
	 * @throws Exceptions\InvalidState
	 */
	private function findChildren(Uuid\UuidInterface $id): array
	{
		$findPropertiesQuery = new Queries\Configuration\FindDeviceMappedProperties();
		$findPropertiesQuery->byParentId($id);

		return $this->devicePropertiesConfigurationRepository->findAllBy(
			$findPropertiesQuery,
			Documents\Devices\Properties\Mapped::class,
		);
	}

}
