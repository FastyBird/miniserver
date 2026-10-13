<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Security;

use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Commands;
use FastyBird\Core\Security\Entities;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function array_column;
use function assert;
use function hash;
use function is_string;
use function preg_match;
use function str_contains;
use function var_export;

/**
 * API keys end to end, through the Accounts module's route group, its Security middlewares and
 * its User, which is what production runs: a key created with fb:api-key:create authenticates as
 * its account, with that account's roles, until it is suspended, deleted or expired, and only
 * while fbCore > security > enable > apiKeys is on.
 *
 * GET /v1/accounts requires the manager or administrator role, so it tells a key with the
 * administrator's roles (200) from a key with the user's roles (403) and from no key (403).
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ApiKeyAuthenticationTest extends Tests\Cases\Unit\DbTestCase
{

	private const string ADMINISTRATOR_ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string USER_ACCOUNT = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	// Accounts in tests/sql/dummy.data.sql that cannot sign in
	private const string BLOCKED_ACCOUNT = 'fe115286-8cfd-41bf-aceb-6ca95baf6fe9';

	private const string DELETED_ACCOUNT = 'fdbe2ce2-3b18-41f1-aaab-c3c56d286eb4';

	private const string UNKNOWN_ACCOUNT = '0b3e4b8a-5c7d-4e2f-9a1b-6c8d0e2f4a6b';

	private const string URL = '/api/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts';

	/**
	 * @throws Throwable
	 */
	public function testCreatedKeyAuthenticatesAsItsAccount(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(StatusCodeInterface::STATUS_OK, $this->request(['X-Api-Key' => $secret]));

		// Only the hash is stored
		$rows = $this->getDb()->fetchAllAssociative('SELECT * FROM fb_security_api_keys');

		self::assertCount(1, $rows);
		self::assertSame(hash('sha256', $secret), $rows[0]['key_hash']);
		self::assertNotSame($secret, $rows[0]['key_hash']);

		// No column holds the secret
		self::assertFalse(str_contains(var_export($rows, true), $secret));
	}

	/**
	 * @throws Throwable
	 */
	public function testKeyGetsTheRolesOfItsAccount(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::USER_ACCOUNT);

		// Signed in, as the user, who may not list accounts
		self::assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $this->request(['X-Api-Key' => $secret]));
	}

	/**
	 * A key signs in as its account, so the own-account routes serve that account, as for a token
	 *
	 * @throws Throwable
	 */
	public function testOwnAccountRoutesServeTheKeysAccount(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		foreach (['/v1/me', '/v1/me/emails', '/v1/me/identities'] as $path) {
			self::assertSame(
				StatusCodeInterface::STATUS_OK,
				$this->request(['X-Api-Key' => $secret], '/api/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . $path),
				$path,
			);
		}
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
		];
	}

	/**
	 * The key stays valid, but its account no longer may sign in: the key is refused, also by a
	 * process that still holds the account, active, from an earlier request
	 *
	 * @throws Throwable
	 */
	#[DataProvider('accountsThatCannotSignIn')]
	public function testKeyOfAnAccountThatCannotSignInIsRefused(string $state): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(StatusCodeInterface::STATUS_OK, $this->request(['X-Api-Key' => $secret], self::URL, false));

		// Another process changes the account; this one does not clear its entity manager
		$this->getDb()->executeStatement(
			'UPDATE fb_accounts_module_accounts SET account_state = ? WHERE account_id = ?',
			[$state, Uuid\Uuid::fromString(self::ADMINISTRATOR_ACCOUNT)->getBytes()],
		);

		self::assertSame(
			StatusCodeInterface::STATUS_UNAUTHORIZED,
			$this->request(['X-Api-Key' => $secret], self::URL, false),
		);
		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => $secret]));
	}

	/**
	 * @throws Throwable
	 */
	public function testKeyOfAnUnknownAccountIsRefused(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = 'b3a1f0c2d4e6f8a0b2c4d6e8f0a2b4c6d8e0f2a4b6c8d0e2f4a6b8c0d2e4f6a8';

		// Written directly: fb:api-key:create refuses an unknown account
		$entityManager = $this->getEntityManager();
		$entityManager->persist(new Entities\ApiKeys\ApiKey(
			Uuid\Uuid::fromString(self::UNKNOWN_ACCOUNT),
			'Integration',
			hash('sha256', $secret),
		));
		$entityManager->flush();

		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => $secret]));
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function accountsThatCannotGetAKey(): array
	{
		return [
			'unknown' => [self::UNKNOWN_ACCOUNT],
			'blocked' => [self::BLOCKED_ACCOUNT],
			'deleted' => [self::DELETED_ACCOUNT],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('accountsThatCannotGetAKey')]
	public function testCreateRefusesAnAccountThatCannotSignIn(string $account): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		self::assertSame(
			Command::FAILURE,
			$this->runCommand(Commands\CreateApiKey::class, ['--name' => 'Integration', '--account' => $account]),
		);
		self::assertEquals(0, $this->getDb()->fetchOne('SELECT COUNT(*) FROM fb_security_api_keys'));
	}

	/**
	 * @throws Throwable
	 */
	public function testWrongOrMissingKey(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => $secret . '0']));
		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => 'wrong']));
		self::assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $this->request([]));
	}

	/**
	 * @throws Throwable
	 */
	public function testSuspendedKeyIsRefused(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(StatusCodeInterface::STATUS_OK, $this->request(['X-Api-Key' => $secret]));

		self::assertSame(Command::SUCCESS, $this->runCommand(Commands\SuspendApiKey::class, ['id' => $this->keyId()]));

		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => $secret]));
	}

	/**
	 * @throws Throwable
	 */
	public function testDeletedKeyIsRefused(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(StatusCodeInterface::STATUS_OK, $this->request(['X-Api-Key' => $secret]));

		self::assertSame(Command::SUCCESS, $this->runCommand(Commands\DeleteApiKey::class, ['id' => $this->keyId()]));

		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => $secret]));

		// The deleted key is still listed, in its state, and without its hash
		$tester = new CommandTester($this->getContainer()->getByType(Commands\ListApiKeys::class));

		self::assertSame(Command::SUCCESS, $tester->execute([]));
		self::assertSame(1, preg_match('/' . $this->keyId() . '\s.*\sdeleted\s/', $tester->getDisplay()));
		self::assertFalse(str_contains($tester->getDisplay(), hash('sha256', $secret)));
	}

	/**
	 * The test clock is frozen at 2020-04-01T12:00:00+00:00
	 *
	 * @throws Throwable
	 */
	public function testExpiredKeyIsRefused(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT, '2020-04-01T12:00:01+00:00');

		self::assertSame(StatusCodeInterface::STATUS_OK, $this->request(['X-Api-Key' => $secret]));

		$this->getDb()->executeStatement(
			'UPDATE fb_security_api_keys SET expires_at = ?',
			['2020-04-01 12:00:00'],
		);
		$this->getEntityManager()->clear();

		self::assertSame(StatusCodeInterface::STATUS_UNAUTHORIZED, $this->request(['X-Api-Key' => $secret]));
	}

	/**
	 * @throws Throwable
	 */
	public function testBearerTokenWinsOverApiKey(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/apiKeysEnabled.neon');

		$secret = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		// The user's token decides, not the administrator's key
		self::assertSame(
			StatusCodeInterface::STATUS_FORBIDDEN,
			$this->request(['Authorization' => 'Bearer ' . self::USER_TOKEN, 'X-Api-Key' => $secret]),
		);

		// An invalid token is refused, the valid key does not rescue it
		self::assertSame(
			StatusCodeInterface::STATUS_UNAUTHORIZED,
			$this->request(['Authorization' => 'Bearer ' . self::INVALID_TOKEN, 'X-Api-Key' => $secret]),
		);

		// A valid token is not refused for a wrong key: the key is not read at all
		self::assertSame(
			StatusCodeInterface::STATUS_OK,
			$this->request(['Authorization' => 'Bearer ' . self::ADMINISTRATOR_TOKEN, 'X-Api-Key' => 'wrong']),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testHeaderIsIgnoredWhenApiKeysAreDisabled(): void
	{
		$secret = 'b3a1f0c2d4e6f8a0b2c4d6e8f0a2b4c6d8e0f2a4b6c8d0e2f4a6b8c0d2e4f6a8';

		$entityManager = $this->getEntityManager();
		$entityManager->persist(new Entities\ApiKeys\ApiKey(
			Uuid\Uuid::fromString(self::ADMINISTRATOR_ACCOUNT),
			'Integration',
			hash('sha256', $secret),
		));
		$entityManager->flush();

		self::assertSame([], $this->getContainer()->findByType(Commands\CreateApiKey::class));

		// Neither the valid key nor a wrong one does anything: both requests are anonymous
		self::assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $this->request(['X-Api-Key' => $secret]));
		self::assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $this->request(['X-Api-Key' => 'wrong']));
	}

	/**
	 * @return non-empty-string the secret
	 *
	 * @throws Throwable
	 */
	private function createKey(string $account, string|null $expiresAt = null): string
	{
		$input = ['--name' => 'Integration', '--account' => $account];

		if ($expiresAt !== null) {
			$input['--expires-at'] = $expiresAt;
		}

		$tester = new CommandTester($this->getContainer()->getByType(Commands\CreateApiKey::class));

		self::assertSame(Command::SUCCESS, $tester->execute($input));
		self::assertSame(1, preg_match('/^([0-9a-f]{64})$/m', $tester->getDisplay(), $matches));

		return $matches[1];
	}

	/**
	 * @param class-string<Command> $type
	 * @param array<string, string> $input
	 *
	 * @throws Throwable
	 */
	private function runCommand(string $type, array $input): int
	{
		$command = $this->getContainer()->getByType($type);
		assert($command instanceof Command);

		return (new CommandTester($command))->execute($input);
	}

	/**
	 * @throws Throwable
	 */
	private function keyId(): string
	{
		$ids = array_column(
			$this->getDb()->fetchAllAssociative('SELECT LOWER(HEX(key_id)) AS id FROM fb_security_api_keys'),
			'id',
		);

		self::assertCount(1, $ids);

		assert(is_string($ids[0]));

		return Uuid\Uuid::fromString($ids[0])->toString();
	}

	/**
	 * @param array<string, string> $headers
	 *
	 * @throws Throwable
	 */
	private function request(array $headers, string $url = self::URL, bool $clear = true): int
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		// Each request starts clean, as each HTTP request does in production, unless the test
		// plays a process that keeps its entity manager
		if ($clear) {
			$this->getEntityManager()->clear();
		}

		$response = $router->handle(new ServerRequest(RequestMethodInterface::METHOD_GET, $url, $headers));

		self::assertTrue($response instanceof Http\Response);

		return $response->getStatusCode();
	}

}
