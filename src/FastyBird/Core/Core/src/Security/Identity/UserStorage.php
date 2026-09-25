<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Override;

/**
 * Application user storage
 */
final class UserStorage implements IUserStorage
{

	private UserIdentity|null $identity = null;

	#[Override]
	public function isAuthenticated(): bool
	{
		return $this->getIdentity() !== null;
	}

	#[Override]
	public function getIdentity(): UserIdentity|null
	{
		return $this->identity;
	}

	#[Override]
	public function setIdentity(UserIdentity|null $identity = null): void
	{
		$this->identity = $identity;
	}

}
