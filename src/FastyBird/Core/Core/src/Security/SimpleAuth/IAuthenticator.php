<?php declare(strict_types = 1);

/**
 * IAuthenticator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           29.08.20
 */

namespace FastyBird\Core\Security\SimpleAuth;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth as Security;

/**
 * Application authenticator interface
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IAuthenticator
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
	public function authenticate(array $credentials): Security\IIdentity;

}
