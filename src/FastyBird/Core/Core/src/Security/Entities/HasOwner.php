<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Entities;

use FastyBird\Core\Security\Mapping\Attribute;

/**
 * Entity owner entity
 */
trait HasOwner
{

	#[Attribute\Owner(on: 'create')]
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
