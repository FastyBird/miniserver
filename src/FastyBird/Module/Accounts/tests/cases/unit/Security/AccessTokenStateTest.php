<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Security;

use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Types;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Events;
use FastyBird\Core\WebSockets\Handshake;
use FastyBird\Core\WebSockets\Subscribers;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use React\Socket;
use Throwable;
use function file_get_contents;
use function is_string;

/**
 * A persisted access token signs its holder in only while it may: the token is active, and so are
 * its identity and its account, the rule Authenticator applies to a password sign-in. Otherwise the
 * request gets exactly the response an unknown token gets, and a session is not refreshed. The
 * process is not cleared between requests here, as a long-running server's WebSocket process is
 * not, so the states are the ones another process wrote meanwhile.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccessTokenStateTest extends Tests\Cases\Unit\DbTestCase
{

	// The account of ADMINISTRATOR_TOKEN, its identity, the token and its refresh token (sql/dummy.data.sql)
	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string IDENTITY = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	private const string ACCESS_TOKEN = '4f6710a1-38aa-4264-9c0c-b45285181270';

	private const string REFRESH_TOKEN = '35be5624-0160-4323-83ee-6f59000934b4';

	private const string SESSION = '/api/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session';

	private const string FIXTURES = __DIR__ . '/../../../fixtures/Controllers';

	/**
	 * @throws Throwable
	 */
	public function testTokenOfAnActiveAccountIsAccepted(): void
	{
		$this->assertRead(StatusCodeInterface::STATUS_OK, self::FIXTURES . '/responses/session/session.read.json');
		$this->assertRead(StatusCodeInterface::STATUS_OK, self::FIXTURES . '/responses/session/session.read.json');
	}

	/**
	 * Correctly signed and unexpired, but not persisted: the response every refused token below gets
	 *
	 * @throws Throwable
	 */
	public function testUnknownTokenIsRefused(): void
	{
		$this->assertRead(StatusCodeInterface::STATUS_OK, self::FIXTURES . '/responses/session/session.read.json');

		$this->getDb()->executeStatement(
			'DELETE FROM fb_security_tokens WHERE token_id IN (?, ?)',
			[
				Uuid\Uuid::fromString(self::REFRESH_TOKEN)->getBytes(),
				Uuid\Uuid::fromString(self::ACCESS_TOKEN)->getBytes(),
			],
		);

		$this->assertRead(StatusCodeInterface::STATUS_FORBIDDEN, self::FIXTURES . '/responses/generic/forbidden.json');
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function accountsThatCannotSignIn(): array
	{
		return [
			'blocked' => [Accounts\Types\AccountState::BLOCKED->value],
			'deleted' => [Accounts\Types\AccountState::DELETED->value],
			'not activated' => [Accounts\Types\AccountState::NOT_ACTIVATED->value],
			'approval waiting' => [Accounts\Types\AccountState::APPROVAL_WAITING->value],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('accountsThatCannotSignIn')]
	public function testTokenOfAnAccountThatCannotSignInIsRefused(string $state): void
	{
		$this->assertRead(StatusCodeInterface::STATUS_OK, self::FIXTURES . '/responses/session/session.read.json');

		$this->setAccountState($state);

		$this->assertRead(StatusCodeInterface::STATUS_FORBIDDEN, self::FIXTURES . '/responses/generic/forbidden.json');
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function identitiesThatCannotSignIn(): array
	{
		return [
			'blocked' => [Accounts\Types\IdentityState::BLOCKED->value],
			'deleted' => [Accounts\Types\IdentityState::DELETED->value],
			'invalid' => [Accounts\Types\IdentityState::INVALID->value],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('identitiesThatCannotSignIn')]
	public function testTokenOfAnIdentityThatCannotSignInIsRefused(string $state): void
	{
		$this->assertRead(StatusCodeInterface::STATUS_OK, self::FIXTURES . '/responses/session/session.read.json');

		$this->getDb()->executeStatement(
			'UPDATE fb_accounts_module_identities SET identity_state = ? WHERE identity_id = ?',
			[$state, Uuid\Uuid::fromString(self::IDENTITY)->getBytes()],
		);

		$this->assertRead(StatusCodeInterface::STATUS_FORBIDDEN, self::FIXTURES . '/responses/generic/forbidden.json');
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function tokensThatAreNotActive(): array
	{
		return [
			'blocked' => [Types\TokenState::BLOCKED->value],
			'deleted' => [Types\TokenState::DELETED->value],
			'expired' => [Types\TokenState::EXPIRED->value],
			'revoked' => [Types\TokenState::REVOKED->value],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('tokensThatAreNotActive')]
	public function testTokenThatIsNotActiveIsRefused(string $state): void
	{
		$this->assertRead(StatusCodeInterface::STATUS_OK, self::FIXTURES . '/responses/session/session.read.json');

		$this->setTokenState(self::ACCESS_TOKEN, $state);

		$this->assertRead(StatusCodeInterface::STATUS_FORBIDDEN, self::FIXTURES . '/responses/generic/forbidden.json');
	}

	/**
	 * @throws Throwable
	 */
	public function testSessionOfAnActiveAccountIsRefreshed(): void
	{
		self::assertSame(StatusCodeInterface::STATUS_CREATED, $this->refresh(null));

		// The two old tokens are replaced by two new ones
		self::assertEquals(4, $this->getDb()->fetchOne('SELECT COUNT(*) FROM fb_security_tokens'));
		self::assertEquals(
			0,
			$this->getDb()->fetchOne(
				'SELECT COUNT(*) FROM fb_security_tokens WHERE token_id = ?',
				[Uuid\Uuid::fromString(self::REFRESH_TOKEN)->getBytes()],
			),
		);
	}

	/**
	 * The refresh token is active and unexpired, and is refused exactly as an unknown one is
	 *
	 * @throws Throwable
	 */
	#[DataProvider('accountsThatCannotSignIn')]
	public function testSessionOfAnAccountThatCannotSignInIsNotRefreshed(string $state): void
	{
		$this->setAccountState($state);

		self::assertSame(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->refresh(self::FIXTURES . '/responses/session/session.update.unknown.json'),
		);

		$this->assertNoTokenMinted();
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('identitiesThatCannotSignIn')]
	public function testSessionOfAnIdentityThatCannotSignInIsNotRefreshed(string $state): void
	{
		$this->getDb()->executeStatement(
			'UPDATE fb_accounts_module_identities SET identity_state = ? WHERE identity_id = ?',
			[$state, Uuid\Uuid::fromString(self::IDENTITY)->getBytes()],
		);

		self::assertSame(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->refresh(self::FIXTURES . '/responses/session/session.update.unknown.json'),
		);

		$this->assertNoTokenMinted();
	}

	/**
	 * The refresh token is active, the access token it was issued with is not
	 *
	 * @throws Throwable
	 */
	#[DataProvider('tokensThatAreNotActive')]
	public function testSessionOfAnAccessTokenThatIsNotActiveIsNotRefreshed(string $state): void
	{
		$this->setTokenState(self::ACCESS_TOKEN, $state);

		self::assertSame(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->refresh(self::FIXTURES . '/responses/session/session.update.unknown.json'),
		);

		$this->assertNoTokenMinted();
	}

	/**
	 * The WebSocket server re-checks the token on every message, through the same identity factory
	 *
	 * @throws Throwable
	 */
	#[DataProvider('accountsThatCannotSignIn')]
	public function testWebSocketClientOfAnAccountThatCannotSignInIsRefused(string $state): void
	{
		$subscriber = new Subscribers\Client(
			$this->getContainer()->getByType(Helpers\Database::class),
			$this->getContainer()->getByType(Identity\TokenReader::class),
			$this->getContainer()->getByType(Identity\TokenValidator::class),
			$this->getContainer()->getByType(Accounts\Security\IdentityFactory::class),
		);

		$client = new Entities\Client(1, $this->createMock(Socket\ConnectionInterface::class));
		$client->setWebSocket(
			new Entities\WebSocket(true, false, $this->createMock(Encoding\RFC6455::class)),
		);

		$handshake = (new Handshake\RequestFactory())->createHttpRequest(
			"GET / HTTP/1.1\r\n"
			. "Host: example.test:8888\r\n"
			. "Upgrade: websocket\r\n"
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\n"
			. "Sec-WebSocket-Version: 13\r\n"
			. 'Authorization: Bearer ' . self::ADMINISTRATOR_TOKEN . "\r\n\r\n",
		);
		self::assertNotNull($handshake);

		$subscriber->incomingMessage(new Events\MessageReceived($client, $handshake, ''));

		self::assertSame(self::IDENTITY, $client->getIdentity()?->getId()->toString());

		$this->setAccountState($state);

		$subscriber->incomingMessage(new Events\MessageReceived($client, $handshake, ''));

		self::assertNull($client->getIdentity());
	}

	/**
	 * @throws Throwable
	 */
	private function assertRead(int $statusCode, string $fixture): void
	{
		$response = $this->getContainer()->getByType(Routing\Router::class)->handle(new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			self::SESSION,
			['authorization' => 'Bearer ' . self::ADMINISTRATOR_TOKEN],
		));

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Support\JsonAssert::assertFixtureMatch($fixture, (string) $response->getBody());
	}

	/**
	 * @throws Throwable
	 */
	private function refresh(string|null $fixture): int
	{
		$body = file_get_contents(self::FIXTURES . '/requests/session/session.update.json');
		self::assertTrue(is_string($body));

		$response = $this->getContainer()->getByType(Routing\Router::class)->handle(new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			self::SESSION,
			[],
			$body,
		));

		self::assertTrue($response instanceof Http\Response);

		if ($fixture !== null) {
			Tests\Support\JsonAssert::assertFixtureMatch($fixture, (string) $response->getBody());
		}

		return $response->getStatusCode();
	}

	/**
	 * @throws Throwable
	 */
	private function assertNoTokenMinted(): void
	{
		// The four tokens of sql/dummy.data.sql, the refreshed pair among them
		self::assertEquals(4, $this->getDb()->fetchOne('SELECT COUNT(*) FROM fb_security_tokens'));
		self::assertEquals(
			2,
			$this->getDb()->fetchOne(
				'SELECT COUNT(*) FROM fb_security_tokens WHERE token_id IN (?, ?)',
				[
					Uuid\Uuid::fromString(self::ACCESS_TOKEN)->getBytes(),
					Uuid\Uuid::fromString(self::REFRESH_TOKEN)->getBytes(),
				],
			),
		);
	}

	/**
	 * Written by another process: this one does not clear its entity manager
	 *
	 * @throws Throwable
	 */
	private function setAccountState(string $state): void
	{
		$this->getDb()->executeStatement(
			'UPDATE fb_accounts_module_accounts SET account_state = ? WHERE account_id = ?',
			[$state, Uuid\Uuid::fromString(self::ACCOUNT)->getBytes()],
		);
	}

	/**
	 * @throws Throwable
	 */
	private function setTokenState(string $token, string $state): void
	{
		$this->getDb()->executeStatement(
			'UPDATE fb_security_tokens SET token_state = ? WHERE token_id = ?',
			[$state, Uuid\Uuid::fromString($token)->getBytes()],
		);
	}

}
