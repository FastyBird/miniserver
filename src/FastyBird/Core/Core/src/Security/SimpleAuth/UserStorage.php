<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Security\SimpleAuth as Security;

/**
 * Application user storage
 */
class UserStorage implements Security\IUserStorage
{

	private IIdentity|null $identity = null;

	public function isAuthenticated(): bool
	{
		return $this->getIdentity() !== null;
	}

	public function getIdentity(): Security\IIdentity|null
	{
		return $this->identity;
	}

	public function setIdentity(Security\IIdentity|null $identity = null): void
	{
		$this->identity = $identity;
	}

}
