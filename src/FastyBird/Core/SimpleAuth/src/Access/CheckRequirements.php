<?php declare(strict_types = 1);

/**
 * CheckRequirements.php
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

namespace FastyBird\Core\SimpleAuth\Access;

/**
 * Requirements checker
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Access
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface CheckRequirements
{

	public function isAllowed(mixed $element): bool;

}
