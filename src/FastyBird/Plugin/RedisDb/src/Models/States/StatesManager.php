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

namespace FastyBird\Plugin\RedisDb\Models\States;

use BackedEnum;
use DateTimeInterface;
use FastyBird\Core\Clock;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
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
use function serialize;
use function sprintf;

/**
 * States manager
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
		private readonly Clients\Client $client,
		private readonly States\StateFactory $stateFactory,
		private readonly Clock\Clock $clock,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @phpstan-return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		int $database = 0,
	): States\State
	{
		$raw = $this->createKey($id, $values, $this->entity::getCreateFields(), $database);

		try {
			$state = $this->stateFactory->create($this->entity, $raw);
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be created',
				[
					'source' => Sources\Plugin::REDISDB->value,
					'type' => 'states-manager',
					'exception' => Logging\Logger::buildException($ex),
					'record' => [
						'id' => $id->toString(),
					],
				],
			);

			$this->client->del($id->toString());

			throw new Exceptions\InvalidState(
				'State could not be created, stored data are not valid',
				$ex->getCode(),
				$ex,
			);
		}

		return $state;
	}

	/**
	 * @phpstan-return T
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function update(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		int $database = 0,
	): States\State|false
	{
		try {
			$raw = $this->updateKey($id, $values, $this->entity::getUpdateFields(), $database);

		} catch (Exceptions\NotUpdated) {
			return false;
		} catch (Exceptions\NotFound) {
			$raw = $this->createKey($id, $values, $this->entity::getCreateFields(), $database);
		}

		try {
			$updatedState = $this->stateFactory->create($this->entity, $raw);
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be updated',
				[
					'source' => Sources\Plugin::REDISDB->value,
					'type' => 'states-manager',
					'exception' => Logging\Logger::buildException($ex),
					'record' => [
						'id' => $id->toString(),
					],
				],
			);

			$this->client->del($id->toString());

			throw new Exceptions\InvalidState(
				'State could not be loaded, stored data are not valid',
				$ex->getCode(),
				$ex,
			);
		}

		return $updatedState;
	}

	public function delete(Uuid\UuidInterface $id, int $database = 0): bool
	{
		return $this->deleteKey($id, $database);
	}

	/**
	 * @param array<string>|array<string, int|string|bool|null> $fields
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	private function createKey(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		array $fields,
		int $database,
	): string
	{
		try {
			$this->client->select($database);

			// Initialize structure
			$data = new stdClass();

			$values->offsetSet(States\State::ID_FIELD, $id->toString());

			foreach ($fields as $field => $default) {
				$value = $default;

				if (is_numeric($field)) {
					$field = $default;

					// If default is not defined => field is required
					if (!is_string($field) || !property_exists($values, $field)) {
						throw new Exceptions\InvalidArgument(sprintf('Value for key "%s" is required', $field));
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

			$this->client->set($id->toString(), Utils\Json::encode($data));

			$raw = $this->client->get($id->toString());

			if ($raw === null) {
				throw new Exceptions\InvalidState('Created state could not be loaded from database');
			}

			return $raw;
		} catch (Exceptions\InvalidArgument | Exceptions\InvalidState $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be created', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param array<string> $fields
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotUpdated
	 */
	private function updateKey(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		array $fields,
		int $database,
	): string
	{
		try {
			$this->client->select($database);

			$raw = $this->client->get($id->toString());

			if (!is_string($raw)) {
				throw new Exceptions\NotFound('Stored record could not be loaded from database');
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
						$value = method_exists($value, '__toString') ? $value->__toString() : serialize($value);
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
				throw new Exceptions\NotUpdated('Stored state is same as update');
			}

			$this->client->set($id->toString(), Utils\Json::encode($data));

			$raw = $this->client->get($id->toString());

			if ($raw === null) {
				throw new Exceptions\InvalidState('Updated state could not be loaded from database');
			}

			return $raw;
		} catch (Exceptions\NotUpdated | Exceptions\InvalidState $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('State could not be updated', $ex->getCode(), $ex);
		}
	}

	private function deleteKey(Uuid\UuidInterface $id, int $database = 0): bool
	{
		try {
			$this->client->select($database);

			return $this->client->del($id->toString());
		} catch (Throwable) {
			// Just ignore error
		}

		return false;
	}

}
