<?php declare(strict_types = 1);

/**
 * StatesManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\RedisDb\Models\States\Async;

use BackedEnum;
use DateTimeInterface;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
use React\Promise;
use stdClass;
use Throwable;
use function array_keys;
use function assert;
use function get_object_vars;
use function in_array;
use function is_numeric;
use function is_object;
use function is_string;
use function method_exists;
use function property_exists;
use function React\Async\async;
use function React\Async\await;
use function serialize;
use function sprintf;

/**
 * Asynchronous states manager
 *
 * @template T of States\State
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesManager
{

	use Nette\SmartObject;

	/**
	 * @param class-string<T> $entity
	 */
	public function __construct(
		private readonly Clients\Async\Client $client,
		private readonly States\StateFactory $stateFactory,
		private readonly DateTimeFactory\Clock $clock,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<T>
	 *
	 * @throws InvalidArgumentException
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		int $database = 0,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->createKey($id, $values, $this->entity::getCreateFields(), $database)
			->then(async(function (string $raw) use ($id, $deferred): void {
				try {
					$state = $this->stateFactory->create($this->entity, $raw);

					$deferred->resolve($state);
				} catch (Throwable $ex) {
					$this->logger->error(
						'Data stored in database are noc compatible with state entity',
						[
							'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
							'type' => 'states-async-manager',
							'record' => [
								'id' => $id->toString(),
								'data' => $raw,
							],
							'exception' => ToolsHelpers\Logger::buildException($ex),
						],
					);

					await($this->client->del($id->toString()));

					$deferred->reject(
						new Exceptions\InvalidState(
							'State could not be created, stored data are not valid',
							$ex->getCode(),
							$ex,
						),
					);
				}
			}))
			->catch(function (Throwable $ex) use ($id, $deferred): void {
				$this->logger->error(
					'State could not be created',
					[
						'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
						'type' => 'states-async-manager',
						'exception' => ToolsHelpers\Logger::buildException($ex),
						'record' => [
							'id' => $id->toString(),
						],
					],
				);

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<T|false>
	 *
	 * @throws InvalidArgumentException
	 */
	public function update(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		int $database = 0,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->updateKey($id, $values, $this->entity::getUpdateFields(), $database)
			->then(async(function (string $raw) use ($id, $deferred): void {
				try {
					$state = $this->stateFactory->create($this->entity, $raw);

					$deferred->resolve($state);
				} catch (Throwable $ex) {
					$this->logger->error(
						'Data stored in database are noc compatible with state entity',
						[
							'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
							'type' => 'states-async-manager',
							'record' => [
								'id' => $id->toString(),
								'data' => $raw,
							],
							'exception' => ToolsHelpers\Logger::buildException($ex),
						],
					);

					await($this->client->del($id->toString()));

					$deferred->reject(
						new Exceptions\InvalidState(
							'State could not be updated, stored data are not valid',
							$ex->getCode(),
							$ex,
						),
					);
				}
			}))
			->catch(function (Throwable $ex) use ($id, $values, $database, $deferred): void {
				if ($ex instanceof Exceptions\NotFound) {
					$this->createKey($id, $values, $this->entity::getCreateFields(), $database)
						->then(async(function (string $raw) use ($id, $deferred): void {
							try {
								$state = $this->stateFactory->create($this->entity, $raw);

								$deferred->resolve($state);
							} catch (Throwable $ex) {
								$this->logger->error(
									'Data stored in database are noc compatible with state entity',
									[
										'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
										'type' => 'states-async-manager',
										'record' => [
											'id' => $id->toString(),
											'data' => $raw,
										],
										'exception' => ToolsHelpers\Logger::buildException($ex),
									],
								);

								await($this->client->del($id->toString()));

								$deferred->reject(
									new Exceptions\InvalidState(
										'State could not be updated, stored data are not valid',
										$ex->getCode(),
										$ex,
									),
								);
							}
						}))
						->catch(function (Throwable $ex) use ($id, $deferred): void {
							$this->logger->error(
								'State could not be updated',
								[
									'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
									'type' => 'states-async-manager',
									'exception' => ToolsHelpers\Logger::buildException($ex),
									'record' => [
										'id' => $id->toString(),
									],
								],
							);

							$deferred->reject($ex);
						});
				} elseif ($ex instanceof Exceptions\NotUpdated) {
					$deferred->resolve(false);
				} else {
					$this->logger->error(
						'State could not be updated',
						[
							'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
							'type' => 'states-async-manager',
							'exception' => ToolsHelpers\Logger::buildException($ex),
							'record' => [
								'id' => $id->toString(),
							],
						],
					);

					$deferred->reject($ex);
				}
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws InvalidArgumentException
	 */
	public function delete(
		Uuid\UuidInterface $id,
		int $database = 0,
	): Promise\PromiseInterface
	{
		return $this->deleteKey($id, $database);
	}

	/**
	 * @param array<string>|array<string, int|string|bool|null> $fields
	 *
	 * @return Promise\PromiseInterface<string>
	 *
	 * @throws InvalidArgumentException
	 */
	private function createKey(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		array $fields,
		int $database,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->client->select($database);

		try {
			// Initialize structure
			$data = new stdClass();

			$values->offsetSet(States\State::ID_FIELD, $id->toString());

			foreach ($fields as $field => $default) {
				$value = $default;

				if (is_numeric($field)) {
					$field = $default;

					// If default is not defined => field is required
					if (!is_string($field) || !property_exists($values, $field)) {
						return Promise\reject(
							new Exceptions\InvalidArgument(sprintf('Value for key "%s" is required', $field)),
						);
					}

					$value = $values->offsetGet($field);

				} elseif (property_exists($values, $field)) {
					if ($values->offsetGet($field) !== null) {
						$value = $values->offsetGet($field);

						if ($value instanceof DateTimeInterface) {
							$value = $value->format(DateTimeInterface::ATOM);
						} elseif ($value instanceof Utils\ArrayHash) {
							$value = (array) $value;
						} elseif ($value instanceof BackedEnum) {
							$value = $value->value;
						} elseif (is_object($value)) {
							$value = method_exists($value, '__toString') ? $value->__toString() : serialize($value);
						}
					} else {
						$value = null;
					}
				} else {
					if ($field === States\State::CREATED_AT_FIELD) {
						$value = $this->clock->getNow()->format(DateTimeInterface::ATOM);
					}
				}

				$data->{$field} = $value;
			}

			$data = Utils\Json::encode($data);
		} catch (Throwable $ex) {
			return Promise\reject(new Exceptions\InvalidState('State could not be created', $ex->getCode(), $ex));
		}

		$this->client->set($id->toString(), $data)
			->then(function () use ($deferred, $id): void {
				$this->client->get($id->toString())
					->then(static function (string|null $raw) use ($deferred): void {
						if ($raw === null) {
							$deferred->reject(
								new Exceptions\NotUpdated('Created state could not be loaded from database'),
							);

							return;
						}

						$deferred->resolve($raw);
					})
					->catch(static function (Throwable $ex) use ($deferred): void {
						$deferred->reject(
							new Exceptions\InvalidState(
								'Created state could not be loaded from database',
								$ex->getCode(),
								$ex,
							),
						);
					});
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject(new Exceptions\InvalidState('State could not be created', $ex->getCode(), $ex));
			});

		return $deferred->promise();
	}

	/**
	 * @param array<string> $fields
	 *
	 * @return Promise\PromiseInterface<string>
	 *
	 * @throws InvalidArgumentException
	 */
	private function updateKey(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		array $fields,
		int $database,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->client->select($database);

		try {
			$this->client->get($id->toString())
				->then(async(function (string|null $raw) use ($id, $fields, $values, $deferred): void {
					if (!is_string($raw)) {
						$deferred->reject(
							new Exceptions\NotFound('Stored record could not be loaded from database'),
						);

						return;
					}

					$data = Utils\Json::decode($raw);
					assert($data instanceof stdClass);

					$isUpdated = false;

					foreach ($fields as $field) {
						if (property_exists($values, $field)) {
							$value = $values->offsetGet($field);

							if ($value instanceof DateTimeInterface) {
								$value = $value->format(DateTimeInterface::ATOM);

							} elseif ($value instanceof Utils\ArrayHash) {
								$value = (array) $value;

							} elseif ($value instanceof BackedEnum) {
								$value = $value->value;

							} elseif (is_object($value)) {
								$value = method_exists($value, '__toString')
									? $value->__toString()
									: serialize(
										$value,
									);
							}

							if (
								!in_array($field, array_keys(get_object_vars($data)), true)
								|| $data->{$field} !== $value
							) {
								$data->{$field} = $value;

								$isUpdated = true;
							}
						} else {
							if ($field === States\State::UPDATED_AT_FIELD) {
								$data->{$field} = $this->clock->getNow()->format(
									DateTimeInterface::ATOM,
								);
							}
						}
					}

					// Save data only if is updated
					if (!$isUpdated) {
						$deferred->reject(new Exceptions\NotUpdated('Stored state is same as update'));

						return;
					}

					$data = Utils\Json::encode($data);

					await($this->client->set($id->toString(), $data));

					$raw = await($this->client->get($id->toString()));

					if ($raw === null) {
						$deferred->reject(
							new Exceptions\NotUpdated('Updated state could not be loaded from database'),
						);

						return;
					}

					$deferred->resolve($raw);
				}))
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		} catch (Throwable $ex) {
			return Promise\reject(new Exceptions\InvalidState('State could not be updated', $ex->getCode(), $ex));
		}

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws InvalidArgumentException
	 */
	private function deleteKey(
		Uuid\UuidInterface $id,
		int $database = 0,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->client->select($database);
		$this->client->del($id->toString())
			->then(static function (bool $result) use ($deferred): void {
				$deferred->resolve($result);
			})
			->catch(static function () use ($deferred): void {
				$deferred->resolve(false);
			});

		return $deferred->promise();
	}

}
