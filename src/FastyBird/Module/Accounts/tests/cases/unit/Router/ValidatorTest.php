<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Router;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Tests;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

/**
 * Router\Validator takes the application router through its constructor (#700), not from the
 * container at runtime. It must answer from that router's routes: a link to a route the module
 * registers is valid, a link to no route is not.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ValidatorTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testValidatesLinksAgainstTheApplicationRoutes(): void
	{
		$validator = $this->getContainer()->getByType(Router\Validator::class);

		self::assertTrue($validator->validate('/api/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '/v1/me'));
		self::assertFalse(
			$validator->validate('/api/' . Accounts\DI\AccountsExtension::MODULE_ACCOUNTS_PREFIX . '/v1/no-such-resource'),
		);
	}

}
