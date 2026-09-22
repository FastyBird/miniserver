<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use Lcobucci\JWT;

/**
 * Application identity factory interface
 */
interface IIdentityFactory
{

	public function create(JWT\UnencryptedToken $token): IIdentity|null;

}
