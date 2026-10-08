<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use Closure;
use DateInterval;
use DateMalformedStringException;
use DateTimeImmutable;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Security\Exceptions as SecurityExceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Tests\Cases\Unit\BaseTestCase;
use JsonException;
use Lcobucci\JWT;
use Nette\DI;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use React\Http\Message\ServerRequest;
use RuntimeException;
use Throwable;
use function base64_encode;
use function file_get_contents;
use function hash_hmac;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function rtrim;
use function strtr;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

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
 *
 * The two refusal tests pin where lcobucci/jwt 5 is stricter than 4.3 about input the application
 * never issues -- an empty signature part, an empty-string claim key -- as accepted by escalation
 * #667. Both inputs are built here from the fixture key and instant; the fixtures stay untouched.
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
		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; the assertion predates it)
		self::assertInstanceOf(DateTimeImmutable::class, $now);

		$token = $this->container->getByType(Identity\TokenBuilder::class)
			->build(self::USER, ['administrator', 'user'], $now->add(new DateInterval('PT1H')));

		$read = $this->container->getByType(Identity\TokenReader::class)
			->read(new ServerRequest('GET', 'http://localhost/api/v1', [
				Identity\TokenReader::HEADER_NAME => 'Bearer ' . $token->toString(),
			]));

		self::assertInstanceOf(JWT\UnencryptedToken::class, $read);
		self::assertSame($token->toString(), $read->toString());
		self::assertSame(self::USER, $read->claims()->get(Identity\TokenBuilder::CLAIM_USER));
		self::assertSame(['administrator', 'user'], $read->claims()->get(Identity\TokenBuilder::CLAIM_ROLES));
		self::assertSame('com.fastybird.auth-module', $read->claims()->get(JWT\Token\RegisteredClaims::ISSUER));
		self::assertEquals($now, $read->claims()->get(JWT\Token\RegisteredClaims::ISSUED_AT));

		$validated = $this->container->getByType(Identity\TokenValidator::class)->validate($token->toString());

		self::assertInstanceOf(JWT\UnencryptedToken::class, $validated);
		self::assertSame(self::USER, $validated->claims()->get(Identity\TokenBuilder::CLAIM_USER));
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
		// @phpstan-ignore staticMethod.alreadyNarrowedType (now() declares it since #641; the assertion predates it)
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
		self::assertSame(
			'5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34',
			$token->claims()->get(Identity\TokenBuilder::CLAIM_USER),
		);
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
	 * @return array<string, array{string}>
	 */
	public static function emptySignatureAlgorithms(): array
	{
		return [
			'HS256' => ['HS256'],
			'none' => ['none'],
		];
	}

	/**
	 * T-a (escalation #667, accepted difference 1): a token whose third part is empty is
	 * structurally broken. lcobucci/jwt 5 refuses it in the parser, so validate() throws where
	 * 4.3 parsed it and returned null; the bearer-header path throws under both. Either way the
	 * token is rejected. A future jwt release that parses it again turns this red.
	 *
	 * @throws DateMalformedStringException
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	#[DataProvider('emptySignatureAlgorithms')]
	public function testATokenWithAnEmptySignaturePartIsRefusedByThrowing(string $algorithm): void
	{
		$token = self::encode(['typ' => 'JWT', 'alg' => $algorithm])
			. '.' . self::encode(self::frozenClaims())
			. '.';

		$validated = self::thrownBy(
			fn (): JWT\Token|null => $this->container->getByType(Identity\TokenValidator::class)->validate($token),
		);

		self::assertInstanceOf(SecurityExceptions\UnauthorizedAccess::class, $validated);
		self::assertSame('Token is not valid JWToken', $validated->getMessage());

		$read = self::thrownBy(
			fn (): JWT\UnencryptedToken|null => $this->container->getByType(Identity\TokenReader::class)
				->readHeader('Bearer ' . $token),
		);

		self::assertInstanceOf(SecurityExceptions\UnauthorizedAccess::class, $read);
	}

	/**
	 * T-b (escalation #667, accepted difference 2): a token signed with the application's own key,
	 * carrying valid claims plus one claim under the empty-string key. lcobucci/jwt 4.3 accepted
	 * it; 5 refuses it in the parser, so validate() throws. TokenBuilder never emits such a key.
	 *
	 * The same claims without the empty key, signed the same way, validate: that is the control
	 * proving the hand-built signature is the application's, so the refusal is the empty key's.
	 *
	 * @throws DateMalformedStringException
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws JsonException
	 * @throws RuntimeException
	 * @throws SecurityExceptions\UnauthorizedAccess
	 */
	public function testACorrectlySignedTokenWithAnEmptyStringClaimKeyIsRefusedByThrowing(): void
	{
		$validator = $this->container->getByType(Identity\TokenValidator::class);
		$header = ['typ' => 'JWT', 'alg' => 'HS256'];
		$claims = self::frozenClaims();

		$control = $validator->validate($this->signed($header, $claims));

		self::assertInstanceOf(JWT\UnencryptedToken::class, $control);
		self::assertSame(
			'5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34',
			$control->claims()->get(Identity\TokenBuilder::CLAIM_USER),
		);

		$token = $this->signed($header, $claims + ['' => 'e5']);

		$validated = self::thrownBy(static fn (): JWT\Token|null => $validator->validate($token));

		self::assertInstanceOf(SecurityExceptions\UnauthorizedAccess::class, $validated);
		self::assertSame('Token is not valid JWToken', $validated->getMessage());
	}

	/**
	 * The claims valid.jwt was minted with, at the fixture's frozen instant, in the encoding
	 * TokenBuilder writes them: `iat` with microseconds, a whole-second `exp` as an integer.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws DateMalformedStringException
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private static function frozenClaims(): array
	{
		$fixture = json_decode(self::read('fixture.json'), true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($fixture) || !is_string($fixture['frozenAt'] ?? null)) {
			throw new RuntimeException('tests/fixtures/tokens/fixture.json is malformed');
		}

		$frozenAt = new DateTimeImmutable($fixture['frozenAt']);

		return [
			'iss' => 'com.fastybird.auth-module',
			'jti' => '8677c940-72cb-4115-9052-61c25643f8d7',
			'iat' => (float) $frozenAt->format('U.u'),
			'exp' => $frozenAt->modify('+100 years')->getTimestamp(),
			'user' => '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34',
			'roles' => ['administrator'],
		];
	}

	/**
	 * Signs header and claims with HS256 under the fixture's key -- the test container's key, as
	 * testTheValidFixtureValidatesToTheClaimsItWasMintedWith asserts -- without lcobucci/jwt, whose
	 * builder cannot produce the inputs these tests need.
	 *
	 * @param array<string, mixed> $header
	 * @param array<string, mixed> $claims
	 *
	 * @throws JsonException
	 * @throws RuntimeException
	 */
	private function signed(array $header, array $claims): string
	{
		$key = $this->fixture()['signature'];

		if (!is_string($key)) {
			throw new RuntimeException('tests/fixtures/tokens/fixture.json is malformed');
		}

		$payload = self::encode($header) . '.' . self::encode($claims);

		return $payload . '.' . self::base64Url(hash_hmac('sha256', $payload, $key, true));
	}

	/**
	 * @param array<string, mixed> $part
	 *
	 * @throws JsonException
	 */
	private static function encode(array $part): string
	{
		return self::base64Url(json_encode($part, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
	}

	private static function base64Url(string $data): string
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * @param Closure(): mixed $call
	 */
	private static function thrownBy(Closure $call): Throwable|null
	{
		try {
			$call();
		} catch (Throwable $ex) {
			return $ex;
		}

		return null;
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
			'user' => $claims->get(Identity\TokenBuilder::CLAIM_USER),
			'roles' => $claims->get(Identity\TokenBuilder::CLAIM_ROLES),
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
