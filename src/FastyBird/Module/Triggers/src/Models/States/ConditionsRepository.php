<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\States;

/**
 * Condition state repository
 */
final readonly class ConditionsRepository
{

	public function __construct(protected IConditionsRepository|null $repository = null)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOne(
		Entities\Conditions\Condition $condition,
	): States\Condition|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Condition state repository is not registered');
		}

		return $this->repository->findOne($condition);
	}

}
