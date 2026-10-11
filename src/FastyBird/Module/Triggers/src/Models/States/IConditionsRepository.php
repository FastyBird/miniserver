<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\States;

/**
 * Condition state repository interface
 */
interface IConditionsRepository
{

	public function findOne(
		Entities\Conditions\Condition $condition,
	): States\Condition|null;

}
