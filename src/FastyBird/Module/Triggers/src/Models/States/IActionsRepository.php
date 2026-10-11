<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\States;

/**
 * Action state repository interface
 */
interface IActionsRepository
{

	public function findOne(Entities\Actions\Action $action): States\Action|null;

}
