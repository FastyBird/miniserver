<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Identity;

use FastyBird\Core\Security\Exceptions;
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

		$configuration = $configuration->withValidationConstraints(
			new JWT\Validation\Constraint\IssuedBy($this->tokenIssuer),
			new JWT\Validation\Constraint\LooseValidAt($this->clock),
			new JWT\Validation\Constraint\SignedWith(
				$configuration->signer(),
				JWT\Signer\Key\InMemory::plainText($this->tokenSignature),
			),
		);

		// lcobucci/jwt 5 types the parser's input as non-empty-string. An empty string is refused
		// exactly as the parser refused it before -- a token without its three parts.
		if ($token === '') {
			throw new Exceptions\UnauthorizedAccess('Token is not valid JWToken');
		}

		try {
			$jwtToken = $configuration->parser()->parse($token);
			assert($jwtToken instanceof JWT\UnencryptedToken);

			$constraints = $configuration->validationConstraints();

			$claims = $jwtToken->claims();

			if (
				$configuration->validator()->validate($jwtToken, ...$constraints)
				&& $claims->has(TokenBuilder::CLAIM_USER)
				&& $claims->has(TokenBuilder::CLAIM_ROLES)
				&& is_string($claims->get(TokenBuilder::CLAIM_USER))
				&& Uuid\Uuid::isValid($claims->get(TokenBuilder::CLAIM_USER))
			) {
				return $jwtToken;
			}
		} catch (Throwable) {
			throw new Exceptions\UnauthorizedAccess('Token is not valid JWToken');
		}

		return null;
	}

}
