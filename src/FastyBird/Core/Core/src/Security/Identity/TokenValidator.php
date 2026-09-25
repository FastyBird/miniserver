<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use DateTimeImmutable;
use FastyBird\Core\Clock as CoreClock;
use FastyBird\Core\Constants;
use FastyBird\Core\Security\Exceptions;
use Lcobucci\Clock as LcobucciClock;
use Lcobucci\JWT;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_string;

/**
 * JW token validator
 */
final readonly class TokenValidator
{

	/**
	 * @param non-empty-string $tokenSignature
	 * @param non-empty-string $tokenIssuer
	 */
	public function __construct(
		private readonly string $tokenSignature,
		private readonly string $tokenIssuer,
		private readonly CoreClock\Clock $clock,
	)
	{
	}

	/**
	 * @return JWT\UnencryptedToken|null
	 *
	 * @throws Exceptions\UnauthorizedAccess
	 */
	public function validate(string $token): JWT\Token|null
	{
		$configuration = JWT\Configuration::forSymmetricSigner(
			new JWT\Signer\Hmac\Sha256(),
			JWT\Signer\Key\InMemory::plainText($this->tokenSignature),
		);

		$now = $this->clock->getNow();
		assert($now instanceof DateTimeImmutable);

		$configuration->setValidationConstraints(
			new JWT\Validation\Constraint\IssuedBy($this->tokenIssuer),
			new JWT\Validation\Constraint\LooseValidAt(new LcobucciClock\FrozenClock($now)),
			new JWT\Validation\Constraint\SignedWith(
				$configuration->signer(),
				JWT\Signer\Key\InMemory::plainText($this->tokenSignature),
			),
		);

		try {
			$jwtToken = $configuration->parser()->parse($token);
			assert($jwtToken instanceof JWT\UnencryptedToken);

			$constraints = $configuration->validationConstraints();

			$claims = $jwtToken->claims();

			if (
				$configuration->validator()->validate($jwtToken, ...$constraints)
				&& $claims->has(Constants::TOKEN_CLAIM_USER)
				&& $claims->has(Constants::TOKEN_CLAIM_ROLES)
				&& is_string($claims->get(Constants::TOKEN_CLAIM_USER))
				&& Uuid\Uuid::isValid($claims->get(Constants::TOKEN_CLAIM_USER))
			) {
				return $jwtToken;
			}
		} catch (Throwable) {
			throw new Exceptions\UnauthorizedAccess('Token is not valid JWToken');
		}

		return null;
	}

}
