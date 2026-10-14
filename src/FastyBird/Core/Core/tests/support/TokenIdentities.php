<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Support;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\Identity;
use Lcobucci\JWT;
use Override;
use Ramsey\Uuid;
use RuntimeException;
use function is_string;

/**
 * Stands in for the module that owns accounts, which Core's tests do not load: a token in
 * $resolvable resolves to its user, any other does not, as a token the accounts module no longer
 * finds active. Counts the lookups, and fails each one while $failing is set
 */
final class TokenIdentities implements Identity\IdentityProvider
{

	/** @var array<string, true> */
	public array $resolvable = [];

	public int $lookups = 0;

	public bool $failing = false;

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function create(JWT\UnencryptedToken $token): Identity\UserIdentity|null
	{
		$this->lookups++;

		if ($this->failing) {
			throw new RuntimeException('Database is gone');
		}

		$user = $token->claims()->get(Identity\TokenBuilder::CLAIM_USER);

		return isset($this->resolvable[$token->toString()]) && is_string($user)
			? new Identity\PlainIdentity($user, ['user'])
			: null;
	}

}
