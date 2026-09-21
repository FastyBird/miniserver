<?php declare(strict_types = 1);

/**
 * TokenValidator.php
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

namespace FastyBird\Core\Security\SimpleAuth;

use DateTimeImmutable;
use FastyBird\Core\Constants\SimpleAuth;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Services\DateTimeFactory;
use Lcobucci\Clock;
use Lcobucci\JWT;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function is_string;

/**
 * JW token validator
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Security
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TokenValidator
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
				&& $claims->has(SimpleAuth\Constants::TOKEN_CLAIM_USER)
				&& $claims->has(SimpleAuth\Constants::TOKEN_CLAIM_ROLES)
				&& is_string($claims->get(SimpleAuth\Constants::TOKEN_CLAIM_USER))
				&& Uuid\Uuid::isValid($claims->get(SimpleAuth\Constants::TOKEN_CLAIM_USER))
			) {
				return $jwtToken;
			}
		} catch (Throwable) {
			throw new Exceptions\UnauthorizedAccess('Token is not valid JWToken');
		}

		return null;
	}

}
