<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Router;

use Error;
use Exception;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Tests;
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
	 * @throws TriggersExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Exception
	 */
	public function testValidatesLinksAgainstTheApplicationRoutes(): void
	{
		$validator = $this->getContainer()->getByType(Router\Validator::class);

		self::assertTrue($validator->validate('/api/' . Triggers\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers'));
		self::assertFalse(
			$validator->validate('/api/' . Triggers\Constants::MODULE_TRIGGERS_PREFIX . '/v1/no-such-resource'),
		);
	}

}
