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

namespace FastyBird\Plugin\RedisDb\Models\States\Async;

use FastyBird\Core\Logging;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use InvalidArgumentException;
use Nette;
use Psr\Log;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function React\Async\async;
use function React\Async\await;

/**
 * Asynchronous state repository
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
		private readonly Clients\Async\Client $client,
		private readonly States\StateFactory $stateFactory,
		private readonly string $entity = States\State::class,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<T|null>
	 *
	 * @throws InvalidArgumentException
	 */
	public function find(Uuid\UuidInterface $id, int $database = 0): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getRaw($id, $database)
			->then(async(function (string|null $raw) use ($id, $deferred): void {
				if ($raw === null) {
					$deferred->resolve(null);

					return;
				}

				try {
					$state = $this->stateFactory->create($this->entity, $raw);

					$deferred->resolve($state);
				} catch (Throwable $ex) {
					$this->logger->error(
						'Data stored in database are noc compatible with state entity',
						[
							'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
							'type' => 'states-async-repository',
							'record' => [
								'id' => $id->toString(),
								'data' => $raw,
							],
							'exception' => Logging\Logger::buildException($ex),
						],
					);

					await($this->client->del($id->toString()));

					$deferred->reject(
						new Exceptions\InvalidState(
							'State could not be loaded, stored data are not valid',
							$ex->getCode(),
							$ex,
						),
					);
				}
			}))
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<string|null>
	 *
	 * @throws InvalidArgumentException
	 */
	private function getRaw(Uuid\UuidInterface $id, int $database): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->client->select($database);
		$this->client->get($id->toString())
			->then(static function (string|null $result) use ($deferred): void {
				$deferred->resolve($result);
			})
			->catch(function (Throwable $ex) use ($id, $deferred): void {
				$this->logger->error(
					'State could not be loaded',
					[
						'source' => MetadataTypes\Sources\Plugin::REDISDB->value,
						'type' => 'states-async-repository',
						'record' => [
							'id' => $id->toString(),
						],
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				$deferred->reject(
					new Exceptions\InvalidState(
						'State could not be loaded from database: ' . $ex->getMessage(),
						0,
						$ex,
					),
				);
			});

		return $deferred->promise();
	}

}
