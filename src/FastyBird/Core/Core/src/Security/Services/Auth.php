<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Services;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authentication service, the work of Middleware\User
 *
 * A bearer token comes first. Only a request that carries none is authenticated by its X-Api-Key
 * header, and only while API keys are enabled (fbCore > security > enable > apiKeys), which is
 * when SecurityExtension hands this service an Identity\ApiKeyReader. A key authenticates as its
 * account, through the identity Identity\AccountIdentityProvider resolves the account to; the
 * account is the Casbin subject.
 */
final class Auth
{

	public function __construct(
		private Identity\TokenReader $tokenReader,
		private Identity\IdentityProvider $identityFactory,
		private Identity\User $user,
		private Identity\ApiKeyReader|null $apiKeyReader = null,
		private Identity\AccountIdentityProvider|null $accountIdentities = null,
	)
	{
	}

	/**
	 * @throws SecurityExceptions\Authentication
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws SecurityExceptions\UnauthorizedAccess
	 */
	public function login(ServerRequestInterface $request): void
	{
		$token = $this->tokenReader->read($request);

		if ($token !== null) {
			// The bearer token wins: when a request carries one, X-Api-Key is not read at all
			$identity = $this->identityFactory->create($token);

			if ($identity !== null) {
				$this->user->login($identity);

				return;
			}

			$this->user->logout();

			return;
		}

		// No bearer token. Whoever an earlier request signed in is signed out first, so a refused
		// key leaves nobody signed in in a long-running process
		$this->user->logout();

		$apiKey = $this->apiKeyReader?->read($request);

		if ($apiKey !== null) {
			// The module that owns accounts decides whether the key's account may sign in. Without
			// one, or for an unknown, blocked or deleted account, the key is refused: fail closed.
			$identity = $this->accountIdentities?->forAccount($apiKey->getAccount());

			if ($identity === null) {
				throw new SecurityExceptions\UnauthorizedAccess('API key is not valid');
			}

			$this->user->login($identity);
		}
	}

}
