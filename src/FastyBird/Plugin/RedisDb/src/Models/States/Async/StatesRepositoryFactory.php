<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Models\States\Async;

use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * Asynchronous state repository factory
 *
 * @template T of States\State
 */
class StatesRepositoryFactory
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Clients\Async\Client $client,
		private readonly States\StateFactory $stateFactory,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @param class-string<T> $entity
	 *
	 * @return StatesRepository<T>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(string $entity = States\State::class): StatesRepository
	{
		if (!class_exists($entity)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided entity class %s does not exists', $entity));
		}

		return new StatesRepository($this->client, $this->stateFactory, $entity, $this->logger);
	}

}
