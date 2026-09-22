<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth\Access;

/**
 * Access checker
 */
interface Checker
{

	public function isAllowed(mixed $element): bool;

}
