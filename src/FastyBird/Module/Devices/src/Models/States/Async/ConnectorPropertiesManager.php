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
 * @date           22.01.24
 */

namespace FastyBird\Module\Devices\Models\States\Async;

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
 * Useful connector dynamic property state helpers
 *
 * @extends Models\States\PropertiesManager<Documents\Connectors\Properties\Dynamic, null, States\ConnectorProperty>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesManager extends Models\States\PropertiesManager
{

	use Nette\SmartObject;

	public function __construct(
		private readonly bool $useExchange,
		private readonly Models\States\Connectors\Async\Repository $connectorPropertyStateRepository,
		private readonly Models\States\Connectors\Async\Manager $connectorPropertiesStatesManager,
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
	 * @return Promise\PromiseInterface<bool|Documents\States\Connectors\Properties\Property|null>
	 */
	public function read(
		Documents\Connectors\Properties\Dynamic $property,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
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
				return Promise\reject(new Exceptions\InvalidState(
					'Requested action could not be published for write action',
					$ex->getCode(),
					$ex,
				));
			}
		} else {
			/** @phpstan-var Documents\States\Connectors\Properties\Property|null $document */
			$document = $this->moduleCaching->getStateCache()->load('read_' . $property->getId()->toString());

			if ($document !== null) {
				return Promise\resolve($document);
			}

			$deferred = new Promise\Deferred();

			$this->readState($property)
				->then(
					function (Documents\States\Connectors\Properties\Property|null $document) use ($deferred, $property): void {
						$this->moduleCaching->getStateCache()->save(
							'read_' . $property->getId()->toString(),
							$document,
							[
								NetteCaching\Cache::Tags => [$property->getId()->toString()],
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function write(
		Documents\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function set(
		Documents\Connectors\Properties\Dynamic $property,
		Utils\ArrayHash $data,
		MetadataTypes\Sources\Source|null $source,
	): Promise\PromiseInterface
	{
		if ($this->useExchange) {
			try {
				return $this->publisher->publish(
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
	 * @param Documents\Connectors\Properties\Dynamic|array<Documents\Connectors\Properties\Dynamic> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function setValidState(
		Documents\Connectors\Properties\Dynamic|array $property,
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
	 * @param Documents\Connectors\Properties\Dynamic|array<Documents\Connectors\Properties\Dynamic> $property
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ValueError
	 * @throws TypeError
	 */
	public function setPendingState(
		Documents\Connectors\Properties\Dynamic|array $property,
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

			$this->connectorPropertiesStatesManager->delete($id)
				->then(function (bool $result) use ($deferred, $id): void {
					$this->dispatcher?->dispatch(new Events\ConnectorPropertyStateEntityDeleted(
						$id,
						MetadataTypes\Sources\Module::DEVICES,
					));

					$deferred->resolve($result);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		} catch (Exceptions\NotImplemented) {
			$this->logger->warning(
				'Connectors states manager is not configured. State could not be fetched',
				[
					'source' => MetadataTypes\Sources\Module::DEVICES->value,
					'type' => 'async-connector-properties-states',
				],
			);
		}

		return Promise\resolve(false);
	}

	/**
	 * @return Promise\PromiseInterface<Documents\States\Connectors\Properties\Property|null>
	 *
	 * @interal
	 */
	public function readState(
		Documents\Connectors\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->connectorPropertyStateRepository->find($property->getId())
			->then(
				function (
					States\ConnectorProperty|null $state,
				) use (
					$deferred,
					$property,
				): void {
					if ($state === null) {
						$deferred->resolve(null);

						return;
					}

					try {
						$readValue = $this->convertStoredState($property, null, $state, true);
						$getValue = $this->convertStoredState($property, null, $state, false);

						$deferred->resolve($this->documentFactory->create(
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
						));
					} catch (Exceptions\InvalidActualValue $ex) {
						$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
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
										'Connectors states manager is not configured. State could not be fetched',
										[
											'source' => MetadataTypes\Sources\Module::DEVICES->value,
											'type' => 'async-connector-properties-states',
										],
									);
								}

								$deferred->reject($ex);
							});

						$this->logger->error(
							'Property stored actual value was not valid',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'async-connector-properties-states',
								'exception' => ToolsHelpers\Logger::buildException($ex),
							],
						);
					} catch (Exceptions\InvalidExpectedValue $ex) {
						$this->connectorPropertiesStatesManager->update($property, $state, Utils\ArrayHash::from([
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
										'Connectors states manager is not configured. State could not be fetched',
										[
											'source' => MetadataTypes\Sources\Module::DEVICES->value,
											'type' => 'async-connector-properties-states',
										],
									);
								}

								$deferred->reject($ex);
							});

						$this->logger->error(
							'Property stored expected value was not valid',
							[
								'source' => MetadataTypes\Sources\Module::DEVICES->value,
								'type' => 'async-connector-properties-states',
								'exception' => ToolsHelpers\Logger::buildException($ex),
							],
						);
					}
				},
			)
			->catch(function (Throwable $ex) use ($deferred): void {
				if ($ex instanceof Exceptions\NotImplemented) {
					$this->logger->warning(
						'Connectors states repository is not configured. State could not be fetched',
						[
							'source' => MetadataTypes\Sources\Module::DEVICES->value,
							'type' => 'async-connector-properties-states',
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
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->connectorPropertyStateRepository->find($property->getId())
			->then(async(
				function (
					States\ConnectorProperty|null $state,
				) use (
					$deferred,
					$data,
					$property,
					$forWriting,
					$source,
				): void {
					/**
					 * IMPORTANT: ACTUAL VALUE field is meant to be used only by connectors for saving device actual value
					 */
					if ($data->offsetExists(States\Property::ACTUAL_VALUE_FIELD)) {
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
									'type' => 'async-connector-properties-states',
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
									null,
									$forWriting,
								);

								if (
									$expectedValue !== null
									&& !$property->isSettable()
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
										'type' => 'async-connector-properties-states',
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
							$result = await($this->connectorPropertiesStatesManager->create(
								$property,
								$data,
							));

						} else {
							$result = await($this->connectorPropertiesStatesManager->update(
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
								'type' => 'async-connector-properties-states',
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
								'Connectors states manager is not configured. State could not be saved',
								[
									'source' => MetadataTypes\Sources\Module::DEVICES->value,
									'type' => 'async-connector-properties-states',
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

}
