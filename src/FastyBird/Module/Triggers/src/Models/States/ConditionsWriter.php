<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Ramsey\Uuid;

/**
 * Conditions states manager interface
 */
interface ConditionsWriter
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array $values): States\Condition;

	/**
	 * @param array<string, mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array $values): States\Condition|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
