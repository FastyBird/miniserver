<?php declare(strict_types = 1);

namespace FastyBird\Core\Entities\SimpleAuth;

use FastyBird\Core\Mapping\SimpleAuth\Attribute as FB;

/**
 * Entity owner entity
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
