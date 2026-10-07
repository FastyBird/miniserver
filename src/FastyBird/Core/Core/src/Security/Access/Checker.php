<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Access;

/**
 * Access checker
 */
interface Checker
{

	public const string PERMISSIONS_DELIMITER = ':';

	public function isAllowed(mixed $element): bool;

}
