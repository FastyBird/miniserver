<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Services;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid;

/**
 * Authentication service, the work of Middleware\User
 *
 * A bearer token comes first. Only a request that carries none is authenticated by its X-Api-Key
 * header, and only while API keys are enabled (fbCore > security > enable > apiKeys), which is
 * when SecurityExtension hands this service an Identity\ApiKeyReader. A key authenticates as its
 * account: the identity's id is the account's id, which is the Casbin subject.
 */
final class Auth
{

	public function __construct(
		private Identity\TokenReader $tokenReader,
		private Identity\IdentityProvider $identityFactory,
		private Identity\User $user,
		private Identity\ApiKeyReader|null $apiKeyReader = null,
	)
	{
	}

	/**
	 * @throws SecurityExceptions\Authentication
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws SecurityExceptions\UnauthorizedAccess
	 * @throws Uuid\Exception\InvalidArgumentException
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

		$apiKey = $this->apiKeyReader?->read($request);

		if ($apiKey !== null) {
			$this->user->login(new Identity\PlainIdentity($apiKey->getAccount()->toString()));

			return;
		}

		$this->user->logout();
	}

}
