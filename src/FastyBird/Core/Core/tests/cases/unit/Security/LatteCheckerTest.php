<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use Casbin\Persist\Adapters\FileAdapter;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Identity;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * LatteChecker reads the array a template's access element is, without wrapping it (#723): a
 * key that is present with a null value is a key that is given, and is checked as one
 */
final class LatteCheckerTest extends TestCase
{

	/**
	 * A logged-in identity the fixture policy maps to role "user", not "administrator".
	 */
	private const string USER_ROLE_IDENTITY = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	/**
	 * @throws Throwable
	 */
	public function testAnElementThatIsNotAnArrayIsAlwaysAllowed(): void
	{
		self::assertTrue($this->checker()->isAllowed('anything'));
		self::assertTrue($this->checker()->isAllowed(null));
	}

	/**
	 * @throws Throwable
	 */
	public function testTheUserParameterIsChecked(): void
	{
		$guest = $this->checker();
		$loggedIn = $this->checker(self::USER_ROLE_IDENTITY);

		self::assertFalse($guest->isAllowed(['user' => 'loggedIn']));
		self::assertTrue($loggedIn->isAllowed(['user' => 'loggedIn']));
		self::assertTrue($guest->isAllowed(['user' => 'guest']));
		self::assertFalse($loggedIn->isAllowed(['user' => 'guest']));
		self::assertTrue($guest->isAllowed([]));
	}

	/**
	 * @throws Throwable
	 */
	public function testAUserParameterGivenAsNullIsRefused(): void
	{
		$this->expectException(Exceptions\InvalidArgument::class);

		$this->checker()->isAllowed(['user' => null]);
	}

	/**
	 * @throws Throwable
	 */
	public function testTheRoleParameterIsChecked(): void
	{
		$loggedIn = $this->checker(self::USER_ROLE_IDENTITY);

		self::assertTrue($loggedIn->isAllowed(['role' => 'user']));
		self::assertTrue($loggedIn->isAllowed(['role' => ['administrator', 'user']]));
		self::assertFalse($loggedIn->isAllowed(['role' => 'administrator']));
	}

	/**
	 * @throws Throwable
	 */
	public function testMoreThanOneResourceIsRefused(): void
	{
		$this->expectException(Exceptions\InvalidArgument::class);

		$this->checker(self::USER_ROLE_IDENTITY)->isAllowed(['resource' => ['one', 'two']]);
	}

	/**
	 * @throws Throwable
	 */
	private function checker(string|null $identity = null): Access\LatteChecker
	{
		$storage = new Identity\UserStorage();

		if ($identity !== null) {
			$identityDouble = $this->createMock(Identity\UserIdentity::class);
			$identityDouble->method('getId')->willReturn(Uuid::fromString($identity));

			$storage->identity = $identityDouble;
		}

		return new Access\LatteChecker(
			new Identity\User(
				$storage,
				new Identity\EnforcerFactory(
					__DIR__ . '/../../../../resources/model.conf',
					new FileAdapter(__DIR__ . '/../../../policy.csv'),
				),
			),
		);
	}

}
