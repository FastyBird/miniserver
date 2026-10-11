<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\States;

use Ramsey\Uuid;

/**
 * Action state interface
 */
interface Action
{

	public function getId(): Uuid\UuidInterface;

	public function isTriggered(): bool;

}
