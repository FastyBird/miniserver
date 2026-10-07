<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Casbin\Exceptions as CasbinExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use Ramsey\Uuid;
use function func_get_args;

/**
 * Application user
 */
class User
{

	public const string ROLE_ANONYMOUS = 'guest';

	public const string ROLE_VISITOR = 'visitor';

	public const string ROLE_USER = 'user';

	public const string ROLE_MANAGER = 'manager';

	public const string ROLE_ADMINISTRATOR = 'administrator';

	public const string ANONYMOUS_ID = 'guest';

	public function __construct(
		protected readonly UserStorage $storage,
		protected readonly EnforcerFactory $enforcerFactory,
		protected readonly Authenticator|null $authenticator = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface|null
	{
		$identity = $this->getIdentity();

		return $identity?->getId();
	}

	public function getIdentity(): UserIdentity|null
	{
		return $this->storage->getIdentity();
	}

	/**
	 * @param string|UserIdentity $user name or instance of UserIdentity
	 *
	 * @throws SecurityExceptions\Authentication
	 * @throws CoreExceptions\InvalidState
	 */
	public function login(string|UserIdentity $user, string|null $password = null): void
	{
		$this->logout();

		if (!$user instanceof UserIdentity) {
			if ($this->authenticator === null) {
				throw new CoreExceptions\InvalidState('Authenticator is not defined');
			}

			$user = $this->authenticator->authenticate(func_get_args());
		}

		$this->storage->setIdentity($user);
	}

	public function logout(): void
	{
		$this->storage->setIdentity(null);
	}

	public function isLoggedIn(): bool
	{
		return $this->storage->isAuthenticated();
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 */
	public function isInRole(string $role): bool
	{
		return $this->enforcerFactory->getEnforcer()->hasRoleForUser(
			$this->getId()?->toString() ?? self::ANONYMOUS_ID,
			$role,
		);
	}

	/**
	 * @return array<string>
	 *
	 * @throws CoreExceptions\InvalidState
	 */
	public function getRoles(): array
	{
		if (!$this->isLoggedIn()) {
			return [self::ROLE_ANONYMOUS];
		}

		return $this->enforcerFactory->getEnforcer()->getRolesForUser(
			$this->getId()?->toString() ?? self::ANONYMOUS_ID,
		);
	}

	/**
	 * @param string $rules
	 *
	 * @throws CoreExceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function isAllowed(...$rules): bool
	{
		try {
			return $this->enforcerFactory->getEnforcer()->enforce(
				$this->getId()?->toString() ?? self::ANONYMOUS_ID,
				...$rules,
			);
		} catch (CasbinExceptions\CasbinException) {
			return false;
		}
	}

}
