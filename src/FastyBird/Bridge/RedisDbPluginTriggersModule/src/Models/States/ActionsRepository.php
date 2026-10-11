<?php declare(strict_types = 1);

namespace FastyBird\Bridge\RedisDbPluginTriggersModule\Models\States;

use FastyBird\Bridge\RedisDbPluginTriggersModule\States;
use FastyBird\Module\Triggers\Documents as TriggersDocuments;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Models as TriggersModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Override;
use Ramsey\Uuid;

/**
 * Trigger action state repository
 */
class ActionsRepository implements TriggersModels\States\ActionsReader
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesRepository<States\Action> */
	private RedisDbModels\States\StatesRepository $stateRepository;

	/**
	 * @param RedisDbModels\States\StatesRepositoryFactory<States\Action> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\Action::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	#[Override]
	public function findOne(
		TriggersDocuments\Actions\Action|TriggersEntities\Actions\Action $action,
	): States\Action|null
	{
		return $this->stateRepository->find($action->getId(), $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOneById(Uuid\UuidInterface $id): States\Action|null
	{
		return $this->stateRepository->find($id, $this->database);
	}

}
