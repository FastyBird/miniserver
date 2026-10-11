<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\States;

/**
 * Action state repository
 */
final readonly class ActionsRepository
{

	public function __construct(protected IActionsRepository|null $repository = null)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOne(Entities\Actions\Action $action): States\Action|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Action state repository is not registered');
		}

		return $this->repository->findOne($action);
	}

}
