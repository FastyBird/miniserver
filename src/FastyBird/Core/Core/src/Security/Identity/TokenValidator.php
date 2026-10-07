<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use FastyBird\Core\Constants;
use FastyBird\Core\Security\Exceptions;
use Lcobucci\Clock;
use Lcobucci\JWT;
use Psr\Clock\ClockInterface;
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
		private readonly ClockInterface $clock,
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

		$now = $this->clock->now();

		$configuration->setValidationConstraints(
			new JWT\Validation\Constraint\IssuedBy($this->tokenIssuer),
			new JWT\Validation\Constraint\LooseValidAt(new Clock\FrozenClock($now)),
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
				&& $claims->has(\FastyBird\Core\Security\Identity\TokenBuilder::CLAIM_USER)
				&& $claims->has(\FastyBird\Core\Security\Identity\TokenBuilder::CLAIM_ROLES)
				&& is_string($claims->get(\FastyBird\Core\Security\Identity\TokenBuilder::CLAIM_USER))
				&& Uuid\Uuid::isValid($claims->get(\FastyBird\Core\Security\Identity\TokenBuilder::CLAIM_USER))
			) {
				return $jwtToken;
			}
		} catch (Throwable) {
			throw new Exceptions\UnauthorizedAccess('Token is not valid JWToken');
		}

		return null;
	}

}
