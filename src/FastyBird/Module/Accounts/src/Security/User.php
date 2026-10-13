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

	#[Override]
	public function getId(): Uuid\UuidInterface|null
	{
		return $this->getAccount()?->getId();
	}

	public function getAccount(): Entities\Accounts\Account|null
	{
		if ($this->isLoggedIn()) {
			$identity = $this->getIdentity();

			if ($identity instanceof Entities\Identities\Identity) {
				return $identity->getAccount();
			}

			// Signed in by an API key, as the account itself
			if ($identity instanceof AccountIdentity) {
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
