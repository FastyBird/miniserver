<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Conditions states manager interface
 *
 * The values to store come as a plain array or as an ArrayHash (#723). An implementation stores an
 * array exactly as the ArrayHash Utils\ArrayHash::from() makes of it.
 */
interface ConditionsWriter
{

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\Condition;

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array|Utils\ArrayHash $values): States\Condition|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
