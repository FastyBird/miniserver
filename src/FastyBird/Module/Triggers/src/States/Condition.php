<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\States;

use Ramsey\Uuid;

/**
 * Condition state interface
 */
interface Condition
{

	public function getId(): Uuid\UuidInterface;

	public function isFulfilled(): bool;

}
