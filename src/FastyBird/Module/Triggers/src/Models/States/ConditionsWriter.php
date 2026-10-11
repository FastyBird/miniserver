<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Conditions states manager interface
 */
interface ConditionsWriter
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
