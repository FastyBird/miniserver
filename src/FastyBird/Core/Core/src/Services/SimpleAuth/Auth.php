<?php declare(strict_types = 1);

namespace FastyBird\Core\Services\SimpleAuth;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as SimpleAuthExceptions;
use FastyBird\Core\Security\SimpleAuth as Security;
use FastyBird\Core\Security\SimpleAuth as SimpleAuthSecurity;
use Nette;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authentication service
 */
final class Auth
{

	use Nette\SmartObject;

	private Security\TokenReader $tokenReader;

	private Security\IIdentityFactory $identityFactory;

	private Security\User $user;

	public function __construct(
		SimpleAuthSecurity\TokenReader $tokenReader,
		SimpleAuthSecurity\IIdentityFactory $identityFactory,
		SimpleAuthSecurity\User $user,
	)
	{
		$this->tokenReader = $tokenReader;
		$this->identityFactory = $identityFactory;

		$this->user = $user;
	}

	/**
	 * @throws SimpleAuthExceptions\Authentication
	 * @throws Exceptions\InvalidState
	 * @throws SimpleAuthExceptions\UnauthorizedAccess
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
