<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Lcobucci\JWT;

/**
 * Application identity factory interface
 */
interface IdentityProvider
{

	public function create(JWT\UnencryptedToken $token): UserIdentity|null;

}
