<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use DateInterval;
use DateTimeImmutable;
use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use JsonException;
use Lcobucci\JWT;
use Nette\DI;
use Psr\Clock\ClockInterface;
use React\Http\Message\ServerRequest;
use RuntimeException;
use Throwable;
use function file_get_contents;
use function is_array;
use function json_decode;
use const JSON_THROW_ON_ERROR;

/**
 * Tokens issued and validated through the container, and the tokens minted once by today's
 * lcobucci/jwt 4.3 code that must keep validating (census T11.3; #643 relies on it).
 *
 * The round trip goes through the services the application uses -- TokenBuilder, TokenReader and
 * TokenValidator, wired with the container's clock -- so it pins the wiring E5.9 (#641) changes
 * when it swaps in PSR-20, as well as the token format.
 *
 * tests/fixtures/tokens/ holds valid.jwt, expired.jwt and foreign-signature.jwt, minted by
 * TokenBuilder on lcobucci/jwt 4.3.0 with a FrozenClock, and fixture.json with every claim, the
 * signing setup and the frozen instant. They are NEVER re-minted. Upgrading to lcobucci/jwt 5
 * must not invalidate a token a running installation already handed out: if this test needs
 * editing for jwt 5, existing sessions break, which is an escalation, not a test update. The
 * Accounts side of the same fixture, resolving valid.jwt to its identity through the persisted
 * token, is Accounts' FixtureTokenIdentityTest.
 */
final class TokenCompatibilityTest extends BaseTestCase
{

	private const string USER = '6f1e2d3c-4b5a-4987-8a6b-5c4d3e2f1a0b';

	private const string FIXTURES = __DIR__ . '/../../../fixtures/tokens';

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Throwable
	 */
	public function testATokenTheContainerIssuesIsReadBackByTheContainer(): void
	{
		$now = $this->container->getByType(ClockInterface::class)->now();
		self::assertInstanceOf(DateTimeImmutable::class, $now);

		$token = $this->container->getByType(Identity\TokenBuilder::class)
			->build(self::USER, ['administrator', 'user'], $now->add(new DateInterval('PT1H')));

		$read = $this->container->getByType(Identity\TokenReader::class)
			->read(new ServerRequest('GET', 'http://localhost/api/v1', [
				Constants::TOKEN_HEADER_NAME => 'Bearer ' . $token->toString(),
			]));

		self::assertInstanceOf(JWT\UnencryptedToken::class, $read);
		self::assertSame($token->toString(), $read->toString());
		self::assertSame(self::USER, $read->claims()->get(Constants::TOKEN_CLAIM_USER));
		self::assertSame(['administrator', 'user'], $read->claims()->get(Constants::TOKEN_CLAIM_ROLES));
		self::assertSame('com.fastybird.auth-module', $read->claims()->get(JWT\Token\RegisteredClaims::ISSUER));
		self::assertEquals($now, $read->claims()->get(JWT\Token\RegisteredClaims::ISSUED_AT));

		$validated = $this->container->getByType(Identity\TokenValidator::class)->validate($token->toString());

		self::assertInstanceOf(JWT\UnencryptedToken::class, $validated);
		self::assertSame(self::USER, $validated->claims()->get(Constants::TOKEN_CLAIM_USER));
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Throwable
	 */
	public function testAnExpiredTokenTheContainerIssuedIsRefused(): void
	{
		$now = $this->container->getByType(ClockInterface::class)->now();
		self::assertInstanceOf(DateTimeImmutable::class, $now);

		$token = $this->container->getByType(Identity\TokenBuilder::class)
			->build(self::USER, ['user'], $now->sub(new DateInterval('PT1S')));

		self::assertNull($this->container->getByType(Identity\TokenValidator::class)->validate($token->toString()));

		$this->expectException(SecurityExceptions\UnauthorizedAccess::class);
		$this->expectExceptionMessage('Access token is not valid');

		$this->container->getByType(Identity\TokenReader::class)->readHeader('Bearer ' . $token->toString());
	}

	/**
	 * valid.jwt validates with the application's signing setup, to exactly the claims it was
	 * minted with -- `iat` to the microsecond, `exp` to the second (4.3 reads the whole-second
	 * `exp` back through a float, as ...30.000001) -- and reads back byte for byte: the string is
	 * the key the accounts module looks the persisted token up by.
	 *
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws JsonException
	 * @throws RuntimeException
	 * @throws SecurityExceptions\UnauthorizedAccess
	 */
	public function testTheValidFixtureValidatesToTheClaimsItWasMintedWith(): void
	{
		$fixture = $this->fixture();
		$minted = self::read('valid.jwt');

		self::assertSame('g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=', $fixture['signature']);
		self::assertSame('com.fastybird.auth-module', $fixture['issuer']);

		$token = $this->container->getByType(Identity\TokenValidator::class)->validate($minted);

		self::assertInstanceOf(JWT\UnencryptedToken::class, $token);
		self::assertSame($minted, $token->toString());
		self::assertSame('HS256', $token->headers()->get('alg'));
		self::assertSame('JWT', $token->headers()->get('typ'));
		self::assertSame($fixture['claims']['valid'], self::claimsOf($token));
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws SecurityExceptions\UnauthorizedAccess
	 */
	public function testTheValidFixtureIsReadFromABearerHeader(): void
	{
		$minted = self::read('valid.jwt');

		$token = $this->container->getByType(Identity\TokenReader::class)->readHeader('Bearer ' . $minted);

		self::assertInstanceOf(JWT\UnencryptedToken::class, $token);
		self::assertSame($minted, $token->toString());
		self::assertSame('5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34', $token->claims()->get(Constants::TOKEN_CLAIM_USER));
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function testTheExpiredAndTheForeignlySignedFixturesAreRefused(): void
	{
		$reader = $this->container->getByType(Identity\TokenReader::class);
		$refused = [];

		foreach (['expired.jwt', 'foreign-signature.jwt'] as $file) {
			try {
				$reader->readHeader('Bearer ' . self::read($file));
			} catch (SecurityExceptions\UnauthorizedAccess $ex) {
				$refused[$file] = $ex->getMessage();
			}
		}

		self::assertSame(
			[
				'expired.jwt' => 'Access token is not valid',
				'foreign-signature.jwt' => 'Access token is not valid',
			],
			$refused,
		);
	}

	/**
	 * @return array{iss: mixed, jti: mixed, iat: string|null, exp: string|null, user: mixed, roles: mixed}
	 */
	private static function claimsOf(JWT\UnencryptedToken $token): array
	{
		$claims = $token->claims();
		$issuedAt = $claims->get(JWT\Token\RegisteredClaims::ISSUED_AT);
		$expiresAt = $claims->get(JWT\Token\RegisteredClaims::EXPIRATION_TIME);

		return [
			'iss' => $claims->get(JWT\Token\RegisteredClaims::ISSUER),
			'jti' => $claims->get(JWT\Token\RegisteredClaims::ID),
			'iat' => $issuedAt instanceof DateTimeImmutable ? $issuedAt->format('Y-m-d\TH:i:s.uP') : null,
			'exp' => $expiresAt instanceof DateTimeImmutable ? $expiresAt->format('Y-m-d\TH:i:sP') : null,
			'user' => $claims->get(Constants::TOKEN_CLAIM_USER),
			'roles' => $claims->get(Constants::TOKEN_CLAIM_ROLES),
		];
	}

	/**
	 * @return array{signature: mixed, issuer: mixed, claims: array{valid: mixed}}
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function fixture(): array
	{
		$fixture = json_decode(self::read('fixture.json'), true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($fixture) || !is_array($fixture['claims'] ?? null) || !isset($fixture['claims']['valid'])) {
			throw new RuntimeException('tests/fixtures/tokens/fixture.json is malformed');
		}

		return [
			'signature' => $fixture['signature'] ?? null,
			'issuer' => $fixture['issuer'] ?? null,
			'claims' => ['valid' => $fixture['claims']['valid']],
		];
	}

	/**
	 * @throws RuntimeException
	 */
	private static function read(string $file): string
	{
		$content = file_get_contents(self::FIXTURES . '/' . $file);

		if ($content === false) {
			throw new RuntimeException('tests/fixtures/tokens/' . $file . ' could not be read');
		}

		return $content;
	}

}
