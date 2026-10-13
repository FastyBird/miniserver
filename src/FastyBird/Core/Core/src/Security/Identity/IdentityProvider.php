<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Lcobucci\JWT;
use SensitiveParameter;

/**
 * Application identity factory interface
 */
interface IdentityProvider
{

	public function create(
		#[SensitiveParameter]
		JWT\UnencryptedToken $token,
	): UserIdentity|null;

}
