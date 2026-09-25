<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

/**
 * Application user storage
 */
interface IUserStorage
{

	public function isAuthenticated(): bool;

	public function setIdentity(UserIdentity|null $identity): void;

	public function getIdentity(): UserIdentity|null;

}
