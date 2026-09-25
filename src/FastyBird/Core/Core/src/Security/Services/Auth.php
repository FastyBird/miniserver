<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Services;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authentication service
 */
final class Auth
{

	private Identity\TokenReader $tokenReader;

	private Identity\IdentityProvider $identityFactory;

	private Identity\User $user;

	public function __construct(
		Identity\TokenReader $tokenReader,
		Identity\IdentityProvider $identityFactory,
		Identity\User $user,
	)
	{
		$this->tokenReader = $tokenReader;
		$this->identityFactory = $identityFactory;

		$this->user = $user;
	}

	/**
	 * @throws SecurityExceptions\Authentication
	 * @throws CoreExceptions\InvalidState
	 * @throws SecurityExceptions\UnauthorizedAccess
	 */
	public function login(ServerRequestInterface $request): void
	{
		$token = $this->tokenReader->read($request);

		if ($token !== null) {
			$identity = $this->identityFactory->create($token);

			if ($identity !== null) {
				$this->user->login($identity);

				return;
			}
		}

		$this->user->logout();
	}

}
