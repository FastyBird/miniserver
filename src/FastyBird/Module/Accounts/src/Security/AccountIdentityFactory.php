<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Security;

use Doctrine\Persistence;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Types;
use Override;
use Ramsey\Uuid;

/**
 * Resolves the account of an API key for Core's Security\Services\Auth. Only an active account
 * signs in, the rule Authenticator applies to a password sign-in: an unknown, blocked, deleted,
 * not activated or approval-waiting account yields null, and the key is refused.
 */
final readonly class AccountIdentityFactory implements Identity\AccountIdentityProvider
{

	public function __construct(
		private Models\Entities\Accounts\AccountsRepository $accountsRepository,
		private Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidState
	 */
	#[Override]
	public function forAccount(Uuid\UuidInterface $account): Identity\UserIdentity|null
	{
		$findAccount = new Queries\Entities\FindAccounts();
		$findAccount->byId($account);

		$entity = $this->accountsRepository->findOneBy($findAccount);

		if ($entity === null) {
			return null;
		}

		// The account may already be in the identity map of a long-running process, with the state
		// it had when it was loaded; read the state another process may have changed since
		$this->managerRegistry->getManagerForClass(Entities\Accounts\Account::class)?->refresh($entity);

		return $entity->getState() === Types\AccountState::ACTIVE ? new AccountIdentity($entity) : null;
	}

}
