<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Access;

/**
 * Requirements checker
 */
interface CheckRequirements
{

	public function isAllowed(mixed $element): bool;

}
