<?php declare(strict_types = 1);

/**
 * StatesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           02.03.20
 */

namespace FastyBird\Plugin\RedisDb\Models\States;

use FastyBird\Core\Logging;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Psr\Log;
use Ramsey\Uuid;
use Throwable;

/**
 * State repository
 *
 * @template T of States\State
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Models
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StatesRepository
{

	use Nette\SmartObject;

	/**
	 * @param class-string<T> $entity
	 */
	public function __construct(
		private readonly Clients\Client $client,
		private readonly States\StateFactory $stateFactory,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
					'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
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
					'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
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
