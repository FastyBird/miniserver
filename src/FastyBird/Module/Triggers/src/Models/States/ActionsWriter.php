<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Actions states manager interface
 *
 * The values to store come as a plain array or as an ArrayHash (#723). An implementation stores an
 * array exactly as the ArrayHash Utils\ArrayHash::from() makes of it.
 */
interface ActionsWriter
{

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\Action;

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\Action|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
