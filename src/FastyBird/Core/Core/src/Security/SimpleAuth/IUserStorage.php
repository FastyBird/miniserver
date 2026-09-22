<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Security\SimpleAuth as Security;

/**
 * Application user storage
 */
interface IUserStorage
{

	public function isAuthenticated(): bool;

	public function setIdentity(Security\IIdentity|null $identity): void;

	public function getIdentity(): Security\IIdentity|null;

}
