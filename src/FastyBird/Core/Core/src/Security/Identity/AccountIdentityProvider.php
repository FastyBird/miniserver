<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use Ramsey\Uuid;

/**
 * Resolves an account to the identity it signs in as, for an API key, which names its account and
 * nothing else. The module that owns accounts implements it and decides whether the account may
 * sign in: an unknown account, or one that may not sign in (blocked, deleted, not activated),
 * yields null and the key is refused. Core ships no implementation, so without such a module no
 * key signs anyone in.
 */
interface AccountIdentityProvider
{

	public function forAccount(Uuid\UuidInterface $account): UserIdentity|null;

}
