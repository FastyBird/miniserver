<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Actions states manager interface
 */
interface ActionsWriter
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Action;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Action|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
