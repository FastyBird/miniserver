<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Security;

use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models\Tokens;
use FastyBird\Core\Security\Queries;
use FastyBird\Core\Security\Types as SecurityTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Types as AccountsTypes;
use Lcobucci\JWT;
use Override;
use SensitiveParameter;

/**
 * Resolves a persisted access token to the identity it was issued to. The token signs in only while
 * it may: the token is active, and so are its identity and its account, the rule Authenticator
 * applies to a password sign-in. Otherwise it yields null, exactly as an unknown token does. The
 * JSON:API user middleware, the presenters and the WebSocket server all resolve tokens here.
 */
final readonly class IdentityFactory implements Identity\IdentityProvider
{

	public function __construct(
		private Tokens\Repository $tokensRepository,
		private Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws AccountsExceptions\InvalidState
	 */
	#[Override]
	public function create(
		#[SensitiveParameter]
		JWT\Token $token,
	): Identity\UserIdentity|null
	{
		/** @var Queries\FindTokens<Entities\Tokens\AccessToken> $findToken */
		$findToken = new Queries\FindTokens();
		$findToken->byToken($token->toString());
		$findToken->inState(SecurityTypes\TokenState::ACTIVE);

		$accessToken = $this->tokensRepository->findOneBy($findToken, Entities\Tokens\AccessToken::class);

		if ($accessToken instanceof Entities\Tokens\AccessToken) {
			return $this->forAccessToken($accessToken);
		}

		return null;
	}

	/**
	 * The identity an access token signs in as, or null when the token, its identity or its account
	 * may no longer sign in. Refreshing a session asks the same question of the refresh token's
	 * access token.
	 *
	 * @throws AccountsExceptions\InvalidState
	 */
	public function forAccessToken(Entities\Tokens\AccessToken $accessToken): Entities\Identities\Identity|null
	{
		if (!$accessToken->isActive()) {
			return null;
		}

		$identity = $accessToken->getIdentity();

		// The identity and its account may already be in the identity map of a long-running process,
		// the WebSocket server's, with the states they had when they were loaded; read the states
		// another process may have changed since
		$manager = $this->managerRegistry->getManagerForClass(Entities\Identities\Identity::class);
		$manager?->refresh($identity);
		$manager?->refresh($identity->getAccount());

		if (
			!$identity->isActive()
			|| $identity->getAccount()->getState() !== AccountsTypes\AccountState::ACTIVE
		) {
			return null;
		}

		return $identity;
	}

}
