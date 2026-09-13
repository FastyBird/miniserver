<?php declare(strict_types = 1);

/**
 * TOwner.php
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

namespace FastyBird\Core\SimpleAuth\Entities;

use FastyBird\Core\SimpleAuth\Mapping\Attribute as FB;

/**
 * Entity owner entity
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
trait TOwner
{

	#[FB\Owner(on: 'create')]
	protected mixed $owner = null;

	public function setOwnerId(string|null $ownerId): void
	{
		$this->owner = $ownerId;
	}

	public function getOwnerId(): string|null
	{
		return $this->owner;
	}

}
