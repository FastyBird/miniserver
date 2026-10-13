<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Security;

use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use Override;
use Ramsey\Uuid;

/**
 * Application user
 */
final class User extends Identity\User
{

	/**
	 * The account's id, which is the Casbin subject. An Accounts identity names its account. Any
	 * other identity is the account itself: Core's Services\Auth signs an API key in as a
	 * PlainIdentity whose id is the key's account.
	 */
	#[Override]
	public function getId(): Uuid\UuidInterface|null
	{
		if ($this->getIdentity() instanceof Entities\Identities\Identity) {
			return $this->getAccount()?->getId();
		}

		return parent::getId();
	}

	public function getAccount(): Entities\Accounts\Account|null
	{
		if ($this->isLoggedIn()) {
			$identity = $this->getIdentity();

			if ($identity instanceof Entities\Identities\Identity) {
				return $identity->getAccount();
			}
		}

		return null;
	}

	public function getName(): string
	{
		if ($this->isLoggedIn()) {
			$account = $this->getAccount();

			return $account?->getName() ?? 'Registered';
		}

		return 'Guest';
	}

}
