<?php declare(strict_types = 1);

/**
 * TokenBuilder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 * @since          0.1.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Core\SimpleAuth\Security;

use DateTimeImmutable;
use FastyBird\Core\SimpleAuth;
use FastyBird\DateTimeFactory;
use Lcobucci\JWT;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function assert;

/**
 * JW token builder
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TokenBuilder
{

	use Nette\SmartObject;

	/**
	 * @param non-empty-string $tokenSignature
	 * @param non-empty-string $tokenIssuer
	 */
	public function __construct(
		private readonly string $tokenSignature,
		private readonly string $tokenIssuer,
		private readonly DateTimeFactory\Clock $clock,
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

		$now = $this->clock->getNow();
		assert($now instanceof DateTimeImmutable);

		$jwtBuilder = $configuration->builder();

		$jwtBuilder->issuedBy($this->tokenIssuer);
		$jwtBuilder->identifiedBy(Uuid\Uuid::uuid4()->toString());
		$jwtBuilder->issuedAt($now);

		if ($expiration !== null) {
			$jwtBuilder->expiresAt($expiration);
		}

		$jwtBuilder->withClaim(SimpleAuth\Constants::TOKEN_CLAIM_USER, $userId);
		$jwtBuilder->withClaim(SimpleAuth\Constants::TOKEN_CLAIM_ROLES, $roles);

		return $jwtBuilder->getToken($configuration->signer(), $configuration->signingKey());
	}

}
