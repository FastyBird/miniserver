<?php declare(strict_types = 1);

/**
 * Owner.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           15.07.20
 */

namespace FastyBird\Core\Entities\SimpleAuth;

/**
 * Entity owner interface
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Owner
{

	public function setOwnerId(string|null $ownerId): void;

	public function getOwnerId(): string|null;

}
