<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Ramsey\Uuid;

/**
 * Actions states manager interface
 */
interface ActionsWriter
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array $values): States\Action;

	/**
	 * @param array<string, mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array $values): States\Action|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
