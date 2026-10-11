<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Models\States;

use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Nette;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * State repository factory
 *
 * @template T of States\State
 */
final readonly class StatesRepositoryFactory
{

	use Nette\SmartObject;

	public function __construct(
		private Clients\Client $client,
		private States\StateFactory $stateFactory,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
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
