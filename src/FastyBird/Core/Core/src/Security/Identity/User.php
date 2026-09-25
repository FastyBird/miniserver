<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Casbin\Exceptions as CasbinExceptions;
use Closure;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use Nette\Utils;
use Ramsey\Uuid;
use function func_get_args;

/**
 * Application user
 */
class User
{

	/** @var array<Closure(User $user): void> */
	public array $onLoggedIn = [];

	/** @var array<Closure(User $user): void> */
	public array $onLoggedOut = [];

	public function __construct(
		protected readonly IUserStorage $storage,
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

		Utils\Arrays::invoke($this->onLoggedIn, $this);
	}

	public function logout(): void
	{
		if ($this->isLoggedIn()) {
			Utils\Arrays::invoke($this->onLoggedOut, $this);
		}

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
			$this->getId()?->toString() ?? Constants::USER_ANONYMOUS,
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
			return [Constants::ROLE_ANONYMOUS];
		}

		return $this->enforcerFactory->getEnforcer()->getRolesForUser(
			$this->getId()?->toString() ?? Constants::USER_ANONYMOUS,
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
				$this->getId()?->toString() ?? Constants::USER_ANONYMOUS,
				...$rules,
			);
		} catch (CasbinExceptions\CasbinException) {
			return false;
		}
	}

}
