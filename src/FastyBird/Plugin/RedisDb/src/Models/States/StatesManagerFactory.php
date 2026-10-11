<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Models\States;

use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Exceptions;
use FastyBird\Plugin\RedisDb\States;
use Psr\Clock\ClockInterface;
use Psr\Log;
use function class_exists;
use function sprintf;

/**
 * States manager factory
 *
 * @template T of States\State
 */
final readonly class StatesManagerFactory
{

	public function __construct(
		private Clients\Client $client,
		private States\StateFactory $stateFactory,
		private ClockInterface $clock,
		private Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @param class-string<T> $entity
	 *
	 * @return StatesManager<T>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(string $entity = States\State::class): StatesManager
	{
		if (!class_exists($entity)) {
			throw new Exceptions\InvalidArgument(sprintf('Provided entity class %s does not exists', $entity));
		}

		return new StatesManager(
			$this->client,
			$this->stateFactory,
			$this->clock,
			$entity,
			$this->logger,
		);
	}

}
