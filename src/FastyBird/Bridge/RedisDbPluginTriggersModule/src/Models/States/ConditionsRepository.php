<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Models\States;

use FastyBird\Bridge\RedisDbPluginTriggersModule\States;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Trigger condition state repository
 */
class ConditionsRepository implements TriggersModels\States\ConditionsReader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesRepository<States\Condition> */
	private RedisDbModels\States\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\StatesRepositoryFactory<States\Condition> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\Condition::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOne(
		TriggersDocuments\Conditions\Condition|TriggersEntities\Conditions\Condition $condition,
	): States\Condition|null
	{
		return $this->stateRepository->find($condition->getId(), $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOneById(Uuid\UuidInterface $id): States\Condition|null
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
