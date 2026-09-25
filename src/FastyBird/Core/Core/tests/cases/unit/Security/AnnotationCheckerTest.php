<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use Casbin\Persist\Adapters\FileAdapter;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth;
use FastyBird\Core\Security\SimpleAuth\Access;
use FastyBird\Core\Tests\Fixtures\Security as FixturesSecurity;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Characterization tests for AnnotationChecker -- #458 §1.5 records that authorization is
 * enforced by regex-parsing `@Secured\…` annotations out of docblocks and that nothing in the
 * test suite exercised it before this. `EnforcerFactory` is `final`, so it is built for real
 * against the fixture model/policy this package already ships for exactly this purpose
 * (`resources/model.conf`, `tests/policy.csv`) rather than mocked; only the interface
 * `Identity\IUserStorage` is a test double.
 */
final class AnnotationCheckerTest extends TestCase
{

	/**
	 * A logged-in identity the fixture policy maps to role "user", not "administrator".
	 */
	private const string USER_ROLE_IDENTITY = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	/**
	 * @throws Throwable
	 */
	private function enforcerFactory(): SimpleAuth\EnforcerFactory
	{
		return new SimpleAuth\EnforcerFactory(
			__DIR__ . '/../../../../resources/model.conf',
			new FileAdapter(__DIR__ . '/../../../policy.csv'),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function user(bool $loggedIn, string|null $identity = null): SimpleAuth\User
	{
		$storage = $this->createMock(SimpleAuth\IUserStorage::class);
		$storage->method('isAuthenticated')->willReturn($loggedIn);

		if ($identity !== null) {
			$identityDouble = $this->createMock(SimpleAuth\IIdentity::class);
			$identityDouble->method('getId')->willReturn(Uuid::fromString($identity));

			$storage->method('getIdentity')->willReturn($identityDouble);
		} else {
			$storage->method('getIdentity')->willReturn(null);
		}

		return new SimpleAuth\User($storage, $this->enforcerFactory());
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public function testGuestIsDeniedByASecuredUserLoggedInAnnotation(): void
	{
		$checker = new Access\AnnotationChecker($this->user(loggedIn: false));

		self::assertFalse($checker->isAllowed(new ReflectionClass(FixturesSecurity\GuestIsDenied::class)));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public function testUserLackingTheRequiredRoleIsDeniedByASecuredRoleAnnotation(): void
	{
		$checker = new Access\AnnotationChecker($this->user(loggedIn: true, identity: self::USER_ROLE_IDENTITY));

		self::assertFalse($checker->isAllowed(new ReflectionClass(FixturesSecurity\RoleIsRequired::class)));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public function testAnUnannotatedElementIsAlwaysAllowed(): void
	{
		$checker = new Access\AnnotationChecker($this->user(loggedIn: false));

		self::assertTrue($checker->isAllowed(new ReflectionClass(FixturesSecurity\Unannotated::class)));
	}

}
