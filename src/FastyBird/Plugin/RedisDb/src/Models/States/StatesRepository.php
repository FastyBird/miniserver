<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Models\States;

use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Psr\Log;
use Ramsey\Uuid;
use Throwable;

/**
 * State repository
 *
 * @template T of States\State
 */
final readonly class StatesRepository
{

	/**
	 * @param class-string<T> $entity
	 */
	public function __construct(
		private Clients\Client $client,
		private States\StateFactory $stateFactory,
		private string $entity = States\State::class,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @phpstan-return T
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function find(Uuid\UuidInterface $id, int $database = 0): States\State|null
	{
		$raw = $this->getRaw($id, $database);

		if ($raw === null) {
			return null;
		}

		try {
			return $this->stateFactory->create($this->entity, $raw);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Data stored in database are noc compatible with state entity',
				[
					'source' => Sources\Plugin::REDISDB->value,
					'type' => 'states-repository',
					'record' => [
						'id' => $id->toString(),
						'data' => $raw,
					],
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			$this->client->del($id->toString());

			throw new Exceptions\InvalidState(
				'State could not be loaded, stored data are not valid',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getRaw(Uuid\UuidInterface $id, int $database): string|null
	{
		try {
			$this->client->select($database);

			return $this->client->get($id->toString());
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be loaded',
				[
					'source' => Sources\Plugin::REDISDB->value,
					'type' => 'states-repository',
					'record' => [
						'id' => $id->toString(),
					],
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			throw new Exceptions\InvalidState(
				'Content could not be loaded from database: ' . $ex->getMessage(),
				0,
				$ex,
			);
		}
	}

}
