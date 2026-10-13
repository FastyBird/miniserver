<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Security;

use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Entities;
use Override;
use Ramsey\Uuid;

/**
 * The identity an API key signs in as: the key's account itself, with no sign-in identity
 * (Entities\Identities\Identity) behind it. User reads the account from it exactly as from a
 * sign-in identity, so the account is the Casbin subject and its roles apply.
 */
final readonly class AccountIdentity implements Identity\UserIdentity
{

	public function __construct(private Entities\Accounts\Account $account)
	{
	}

	public function getAccount(): Entities\Accounts\Account
	{
		return $this->account;
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->account->getId();
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getRoles(): array
	{
		return [];
	}

}
