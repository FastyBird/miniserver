<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Constants as SimpleAuth;
use FastyBird\Core\Exceptions;
use Lcobucci\JWT;
use function is_array;
use function is_string;

/**
 * Application plain identity factory
 */
class IdentityFactory implements IIdentityFactory
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(JWT\UnencryptedToken $token): IIdentity|null
	{
		$claims = $token->claims();

		return is_string($claims->get(SimpleAuth\Constants::TOKEN_CLAIM_USER))
		&& is_array($claims->get(SimpleAuth\Constants::TOKEN_CLAIM_ROLES))
			? new PlainIdentity(
				$claims->get(SimpleAuth\Constants::TOKEN_CLAIM_USER),
				$claims->get(SimpleAuth\Constants::TOKEN_CLAIM_ROLES),
			)
			: null;
	}

}
