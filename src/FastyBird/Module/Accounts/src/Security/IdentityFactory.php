<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Security;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models\Tokens;
use FastyBird\Core\Security\Queries;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use Lcobucci\JWT;
use Override;

/**
 * Application identity factory
 */
readonly class IdentityFactory implements Identity\IdentityProvider
{

	public function __construct(private Tokens\Repository $tokensRepository)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws AccountsExceptions\InvalidState
	 */
	#[Override]
	public function create(JWT\Token $token): Identity\UserIdentity|null
	{
		/** @var Queries\FindTokens<Entities\Tokens\AccessToken> $findToken */
		$findToken = new Queries\FindTokens();
		$findToken->byToken($token->toString());

		$accessToken = $this->tokensRepository->findOneBy($findToken, Entities\Tokens\AccessToken::class);

		if ($accessToken instanceof Entities\Tokens\AccessToken) {
			return $accessToken->getIdentity();
		}

		return null;
	}

}
