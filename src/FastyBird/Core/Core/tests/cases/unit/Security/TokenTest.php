<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use FastyBird\Core\Clock;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Security\SimpleAuth;
use Lcobucci\JWT;
use PHPUnit\Framework\TestCase;
use React\Http\Message\ServerRequest;
use Throwable;
use ValueError;

final class TokenTest extends TestCase
{

	private const string SIGNATURE = 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAXaVDBmOxCZorqVGRFb';

	private const string ISSUER = 'com.fastybird.miniserver';

	private const string NOW = '2026-09-21T12:00:00+00:00';

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws DateMalformedStringException
	 * @throws ValueError
	 */
	private function clock(string $at = self::NOW): Clock\FrozenClock
	{
		return new Clock\FrozenClock(new DateTimeImmutable($at));
	}

	/**
	 * Builds a token that satisfies the validator's signature, issuer and expiry checks but
	 * carries only the given application claims. TokenBuilder always sets both
	 * TOKEN_CLAIM_USER and TOKEN_CLAIM_ROLES, so it cannot produce a token missing either one;
	 * this bypasses it to exercise the claims-presence checks in TokenValidator::validate()
	 * in isolation.
	 *
	 * @param array<string, mixed> $claims
	 *
	 * @throws DateMalformedStringException
	 * @throws JWT\Encoding\CannotEncodeContent
	 * @throws JWT\Signer\CannotSignPayload
	 * @throws JWT\Signer\Ecdsa\ConversionFailed
	 * @throws JWT\Signer\InvalidKeyProvided
	 * @throws JWT\Token\RegisteredClaimGiven
	 */
	private function tokenWithClaims(array $claims): string
	{
		$configuration = JWT\Configuration::forSymmetricSigner(
			new JWT\Signer\Hmac\Sha256(),
			JWT\Signer\Key\InMemory::plainText(self::SIGNATURE),
		);

		$builder = $configuration->builder()
			->issuedBy(self::ISSUER)
			->issuedAt(new DateTimeImmutable(self::NOW));

		foreach ($claims as $name => $value) {
			$builder = $builder->withClaim($name, $value);
		}

		return $builder->getToken($configuration->signer(), $configuration->signingKey())->toString();
	}

	/**
	 * @throws Throwable
	 */
	public function testBuiltTokenCarriesTheUserAndRoleClaims(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['administrator', 'user']);

		self::assertSame(
			'9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			$token->claims()->get(Constants\Constants::TOKEN_CLAIM_USER),
		);
		self::assertSame(
			['administrator', 'user'],
			$token->claims()->get(Constants\Constants::TOKEN_CLAIM_ROLES),
		);
		self::assertSame(self::ISSUER, $token->claims()->get(JWT\Token\RegisteredClaims::ISSUER));
	}

	/**
	 * @throws Throwable
	 */
	public function testIssuedAtComesFromTheClockNotTheWallClock(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		$issuedAt = $token->claims()->get(JWT\Token\RegisteredClaims::ISSUED_AT);
		self::assertInstanceOf(DateTimeImmutable::class, $issuedAt);
		self::assertSame(
			(new DateTimeImmutable(self::NOW))->getTimestamp(),
			$issuedAt->getTimestamp(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testEveryTokenGetsADistinctIdentifier(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());

		$first = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);
		$second = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		self::assertNotSame(
			$first->claims()->get(JWT\Token\RegisteredClaims::ID),
			$second->claims()->get(JWT\Token\RegisteredClaims::ID),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorAcceptsATokenThisBuilderProduced(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['user']);

		$validated = $validator->validate($token->toString());

		self::assertInstanceOf(JWT\UnencryptedToken::class, $validated);
		self::assertSame(
			'9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			$validated->claims()->get(Constants\Constants::TOKEN_CLAIM_USER),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsADifferentSignature(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(
			'Nq7ZBvAaP2sXtYuEwR5cV8bN1mK4jH6gF9dS3aQ0zL',
			self::ISSUER,
			$this->clock(),
		);

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		self::assertNull($validator->validate($token->toString()));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsADifferentIssuer(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, 'com.example.other', $this->clock());
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', []);

		self::assertNull($validator->validate($token->toString()));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsAnExpiredToken(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(
			self::SIGNATURE,
			self::ISSUER,
			$this->clock('2026-09-21T14:00:00+00:00'),
		);

		$token = $builder->build(
			'9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
			[],
			new DateTimeImmutable('2026-09-21T13:00:00+00:00'),
		);

		self::assertNull($validator->validate($token->toString()));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsGarbage(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		self::expectException(Exceptions\UnauthorizedAccess::class);

		$validator->validate('not-a-jwt');
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsATokenWithoutAUserClaim(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $this->tokenWithClaims([
			Constants\Constants::TOKEN_CLAIM_ROLES => ['user'],
		]);

		self::assertNull($validator->validate($token));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsATokenWithoutARolesClaim(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $this->tokenWithClaims([
			Constants\Constants::TOKEN_CLAIM_USER => '9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a',
		]);

		self::assertNull($validator->validate($token));
	}

	/**
	 * @throws Throwable
	 */
	public function testValidatorRejectsATokenWithANonUuidUserClaim(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());

		$token = $this->tokenWithClaims([
			Constants\Constants::TOKEN_CLAIM_USER => 'not-a-uuid',
			Constants\Constants::TOKEN_CLAIM_ROLES => ['user'],
		]);

		self::assertNull($validator->validate($token));
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderExtractsABearerToken(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());
		$reader = new SimpleAuth\TokenReader($validator);

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['user']);

		$request = (new ServerRequest('GET', '/api/v1/devices'))
			->withHeader(Constants\Constants::TOKEN_HEADER_NAME, 'Bearer ' . $token->toString());

		$read = $reader->read($request);

		self::assertInstanceOf(JWT\UnencryptedToken::class, $read);
		self::assertSame($token->toString(), $read->toString());
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderReturnsNullWithoutAnAuthorizationHeader(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());
		$reader = new SimpleAuth\TokenReader($validator);

		self::assertNull($reader->read(new ServerRequest('GET', '/api/v1/devices')));
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderIgnoresAHeaderWithoutTheBearerPrefix(): void
	{
		$validator = new SimpleAuth\TokenValidator(self::SIGNATURE, self::ISSUER, $this->clock());
		$reader = new SimpleAuth\TokenReader($validator);

		$request = (new ServerRequest('GET', '/api/v1/devices'))
			->withHeader(Constants\Constants::TOKEN_HEADER_NAME, 'Basic dXNlcjpwYXNz');

		self::assertNull($reader->read($request));
	}

	/**
	 * @throws Throwable
	 */
	public function testReaderThrowsWhenTheBearerTokenFailsValidation(): void
	{
		$builder = new SimpleAuth\TokenBuilder(self::SIGNATURE, self::ISSUER, $this->clock());
		$validator = new SimpleAuth\TokenValidator(
			'Nq7ZBvAaP2sXtYuEwR5cV8bN1mK4jH6gF9dS3aQ0zL',
			self::ISSUER,
			$this->clock(),
		);
		$reader = new SimpleAuth\TokenReader($validator);

		$token = $builder->build('9b1d2b4e-0a1e-4a6a-9d3f-1f2e3d4c5b6a', ['user']);

		$request = (new ServerRequest('GET', '/api/v1/devices'))
			->withHeader(Constants\Constants::TOKEN_HEADER_NAME, 'Bearer ' . $token->toString());

		self::expectException(Exceptions\UnauthorizedAccess::class);

		$reader->read($request);
	}

}
