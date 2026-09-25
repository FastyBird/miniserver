<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use FastyBird\Core\Constants as SimpleAuth;
use FastyBird\Core\Exceptions;
use Lcobucci\JWT;
use Override;
use Ramsey\Uuid;
use function is_array;
use function is_string;

/**
 * Application plain identity factory
 */
final class IdentityFactory implements IdentityProvider
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function create(JWT\UnencryptedToken $token): UserIdentity|null
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
