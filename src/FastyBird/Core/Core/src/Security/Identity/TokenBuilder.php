<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use DateTimeImmutable;
use Lcobucci\JWT;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid;
use Throwable;

/**
 * JW token builder
 */
final readonly class TokenBuilder
{

	public const string CLAIM_USER = 'user';

	public const string CLAIM_ROLES = 'roles';

	/**
	 * @param non-empty-string $tokenSignature
	 * @param non-empty-string $tokenIssuer
	 */
	public function __construct(
		private readonly string $tokenSignature,
		private readonly string $tokenIssuer,
		private readonly ClockInterface $clock,
	)
	{
	}

	/**
	 * @param array<string> $roles
	 *
	 * @throws Throwable
	 */
	public function build(
		string $userId,
		array $roles,
		DateTimeImmutable|null $expiration = null,
	): JWT\UnencryptedToken
	{
		$configuration = JWT\Configuration::forSymmetricSigner(
			new JWT\Signer\Hmac\Sha256(),
			JWT\Signer\Key\InMemory::plainText($this->tokenSignature),
		);

		$now = $this->clock->now();

		// The builder is immutable since lcobucci/jwt 5: every call returns a new builder, so
		// each result is kept. The claims are added in the order they always were, which is
		// the order they are encoded in.
		$jwtBuilder = $configuration->builder()
			->issuedBy($this->tokenIssuer)
			->identifiedBy(Uuid\Uuid::uuid4()->toString())
			->issuedAt($now);

		if ($expiration !== null) {
			$jwtBuilder = $jwtBuilder->expiresAt($expiration);
		}

		$jwtBuilder = $jwtBuilder
			->withClaim(self::CLAIM_USER, $userId)
			->withClaim(self::CLAIM_ROLES, $roles);

		return $jwtBuilder->getToken($configuration->signer(), $configuration->signingKey());
	}

}
