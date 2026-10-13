<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Support;

use FastyBird\Core\Security\Identity;
use Override;
use Ramsey\Uuid;
use function in_array;

/**
 * Stands in for the module that owns accounts, which Core's tests do not load: the accounts in
 * $active can sign in, as a PlainIdentity whose id is the account; any other account cannot.
 */
final class AccountIdentities implements Identity\AccountIdentityProvider
{

	/**
	 * @param list<string> $active
	 */
	public function __construct(public array $active = [])
	{
	}

	/**
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function forAccount(Uuid\UuidInterface $account): Identity\UserIdentity|null
	{
		return in_array($account->toString(), $this->active, true)
			? new Identity\PlainIdentity($account->toString())
			: null;
	}

}
