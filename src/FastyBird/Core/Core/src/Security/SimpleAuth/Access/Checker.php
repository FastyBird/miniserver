<?php declare(strict_types = 1);

/**
 * Checker.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           24.07.24
 */

namespace FastyBird\Core\Security\SimpleAuth\Access;

/**
 * Access checker
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Access
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface Checker
{

	public function isAllowed(mixed $element): bool;

}
