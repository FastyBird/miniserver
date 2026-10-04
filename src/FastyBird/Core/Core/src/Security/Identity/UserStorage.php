<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

/**
 * Application user storage
 */
final class UserStorage
{

	private UserIdentity|null $identity = null;

	public function isAuthenticated(): bool
	{
		return $this->getIdentity() !== null;
	}

	public function getIdentity(): UserIdentity|null
	{
		return $this->identity;
	}

	public function setIdentity(UserIdentity|null $identity = null): void
	{
		$this->identity = $identity;
	}

}
