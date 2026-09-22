<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth\Access;

/**
 * Requirements checker
 */
interface CheckRequirements
{

	public function isAllowed(mixed $element): bool;

}
