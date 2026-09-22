<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Security\SimpleAuth as Security;
use Override;

/**
 * Application user storage
 */
final class UserStorage implements Security\IUserStorage
{

	private IIdentity|null $identity = null;

	#[Override]
	public function isAuthenticated(): bool
	{
		return $this->getIdentity() !== null;
	}

	#[Override]
	public function getIdentity(): Security\IIdentity|null
	{
		return $this->identity;
	}

	#[Override]
	public function setIdentity(Security\IIdentity|null $identity = null): void
	{
		$this->identity = $identity;
	}

}
