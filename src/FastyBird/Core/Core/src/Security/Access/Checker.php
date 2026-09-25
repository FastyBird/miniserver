<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Access;

/**
 * Access checker
 */
interface Checker
{

	public function isAllowed(mixed $element): bool;

}
