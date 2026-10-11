<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginDevicesModule\Models\States\Async;

use FastyBird\Bridge\RedisDbPluginDevicesModule\States;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use InvalidArgumentException;
use Override;
use Ramsey\Uuid;
use React\Promise;

/**
 * Connector property asynchronous states repository
 */
final class ConnectorPropertiesRepository implements DevicesModels\States\Connectors\Async\Reader
{

	/** @var RedisDbModels\States\Async\StatesRepository<States\ConnectorProperty> */
	private RedisDbModels\States\Async\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\Async\StatesRepositoryFactory<States\ConnectorProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\Async\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\ConnectorProperty::class);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
