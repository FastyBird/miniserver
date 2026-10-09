<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

/**
 * Application user storage
 */
final class UserStorage
{

	public UserIdentity|null $identity = null;

	public function isAuthenticated(): bool
	{
		return $this->identity !== null;
	}

}
