<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use FastyBird\Core\Security\Exceptions;

/**
 * Application authenticator interface
 */
interface Authenticator
{

	// Credential key
	public const int USERNAME = 0;

	public const int PASSWORD = 1;

	// Exception error code
	public const int IDENTITY_NOT_FOUND = 1;

	public const int INVALID_CREDENTIAL = 2;

	public const int FAILURE = 3;

	public const int NOT_APPROVED = 4;

	/**
	 * @param array<mixed> $credentials
	 *
	 * @throws Exceptions\Authentication
	 */
	public function authenticate(array $credentials): UserIdentity;

}
