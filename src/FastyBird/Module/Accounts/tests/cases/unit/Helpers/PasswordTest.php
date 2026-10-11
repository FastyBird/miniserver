<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Helpers;

use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function testPassword(): void
	{
		$password = new Helpers\Password(null, 'somePassword');
		$hashedPassword = new Helpers\Password($password->getHash(), null, $password->salt);

		self::assertTrue($hashedPassword->isEqual('somePassword'));
	}

}
