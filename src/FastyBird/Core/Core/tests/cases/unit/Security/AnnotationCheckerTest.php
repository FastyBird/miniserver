<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use Casbin\Persist\Adapters\FileAdapter;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Identity;
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
 * (`resources/model.conf`, `tests/policy.csv`) rather than mocked, and so is
 * `Identity\UserStorage`; only the identity is a test double.
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
	private function enforcerFactory(): Identity\EnforcerFactory
	{
		return new Identity\EnforcerFactory(
			__DIR__ . '/../../../../resources/model.conf',
			new FileAdapter(__DIR__ . '/../../../policy.csv'),
		);
	}

	/**
	 * A guest without an identity, or a user logged in as it: a UserStorage is authenticated
	 * exactly when it holds an identity.
	 *
	 * @throws Throwable
	 */
	private function user(string|null $identity = null): Identity\User
	{
		$storage = new Identity\UserStorage();

		if ($identity !== null) {
			$identityDouble = $this->createMock(Identity\UserIdentity::class);
			$identityDouble->method('getId')->willReturn(Uuid::fromString($identity));

			$storage->identity = $identityDouble;
		}

		return new Identity\User($storage, $this->enforcerFactory());
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public function testGuestIsDeniedByASecuredUserLoggedInAnnotation(): void
	{
		$checker = new Access\AnnotationChecker($this->user());

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
		$checker = new Access\AnnotationChecker($this->user(self::USER_ROLE_IDENTITY));

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
		$checker = new Access\AnnotationChecker($this->user());

		self::assertTrue($checker->isAllowed(new ReflectionClass(FixturesSecurity\Unannotated::class)));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Throwable
	 */
	public function testCheckAccessEvaluatesTheAnnotationsOfAnExistingMethod(): void
	{
		$guest = new Access\AnnotationChecker($this->user());
		$loggedIn = new Access\AnnotationChecker(
			$this->user(self::USER_ROLE_IDENTITY),
		);

		self::assertFalse($guest->checkAccess(FixturesSecurity\MethodIsSecured::class, 'read'));
		self::assertTrue($loggedIn->checkAccess(FixturesSecurity\MethodIsSecured::class, 'read'));
		self::assertTrue($guest->checkAccess(FixturesSecurity\MethodIsSecured::class, null));
	}

	/**
	 * A route or link to a method that does not exist is a wiring error, not an access decision
	 * (#607). Reporting it as "not allowed" answered 403 to every caller and hid the broken
	 * target (#586, #596).
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Throwable
	 */
	public function testCheckAccessToAMissingMethodThrowsInvalidState(): void
	{
		$checker = new Access\AnnotationChecker(
			$this->user(self::USER_ROLE_IDENTITY),
		);

		$this->expectException(Exceptions\InvalidState::class);
		$this->expectExceptionMessage(
			'Access check targets ' . FixturesSecurity\MethodIsSecured::class . '::missing(), which does not exist',
		);

		$checker->checkAccess(FixturesSecurity\MethodIsSecured::class, 'missing');
	}

}
