<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use DateTimeImmutable;
use Doctrine\DBAL;
use Doctrine\ORM;
use FastyBird\Core\Security\Commands;
use FastyBird\Core\Security\Exceptions;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Models;
use FastyBird\Core\Security\Services;
use FastyBird\Core\Security\Types;
use FastyBird\Core\Tests;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Psr\Log;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function array_column;
use function assert;
use function hash;
use function is_string;
use function json_encode;
use function preg_match;
use function str_contains;
use function str_repeat;
use function substr_count;
use function trim;
use function var_export;

/**
 * API keys against a real database, in a container with fbCore > security > enable > apiKeys on:
 * the commands, Identity\ApiKeyReader and Services\Auth, the work of Middleware\User
 *
 * The test clock is frozen at 2020-04-01T12:00:00+00:00, and tests/policy.csv gives
 * ADMINISTRATOR_ACCOUNT the administrator role and USER_ACCOUNT the user role.
 */
final class ApiKeysTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ADMINISTRATOR_ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string USER_ACCOUNT = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	private const string UNKNOWN_KEY = 'a5ca9b5e-1f5b-4a0c-9d0b-2a7c3f1e9b11';

	private const string UNKNOWN_ACCOUNT = '0b3e4b8a-5c7d-4e2f-9a1b-6c8d0e2f4a6b';

	/**
	 * @throws Throwable
	 */
	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createContainer(__DIR__ . '/apiKeysAccounts.neon');

		$entityManager = $this->container->getByType(ORM\EntityManagerInterface::class);

		(new ORM\Tools\SchemaTool($entityManager))->createSchema(
			$entityManager->getMetadataFactory()->getAllMetadata(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testRequestWithoutTheHeaderYieldsNoKey(): void
	{
		$reader = $this->container->getByType(Identity\ApiKeyReader::class);

		$this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertNull($reader->read(new ServerRequest('GET', '/api/v1/devices')));
		self::assertNull($reader->read(new ServerRequest('GET', '/api/v1/devices', ['X-Api-Key' => ''])));
	}

	/**
	 * @throws Throwable
	 */
	public function testCreatedKeyIsFoundByItsSecret(): void
	{
		[$secret, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$reader = $this->container->getByType(Identity\ApiKeyReader::class);

		self::assertSame($id, $reader->read($this->request($secret))?->getId()->toString());

		// Header names are case-insensitive
		self::assertSame(
			$id,
			$reader->read(new ServerRequest('GET', '/api/v1/devices', ['x-api-key' => $secret]))?->getId()->toString(),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testUnknownKeyIsRefused(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$this->assertRefused($secret . '0');
		$this->assertRefused(hash('sha256', $secret));
		$this->assertRefused('wrong');
	}

	/**
	 * @throws Throwable
	 */
	public function testSuspendedKeyIsRefused(): void
	{
		[$secret, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(Command::SUCCESS, $this->execute(Commands\SuspendApiKey::class, ['id' => $id]));

		$this->assertState($id, Types\ApiKeyState::SUSPENDED);
		$this->assertRefused($secret);
	}

	/**
	 * @throws Throwable
	 */
	public function testDeletedKeyIsRefused(): void
	{
		[$secret, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(Command::SUCCESS, $this->execute(Commands\DeleteApiKey::class, ['id' => $id]));

		$this->assertState($id, Types\ApiKeyState::DELETED);
		$this->assertRefused($secret);
	}

	/**
	 * @throws Throwable
	 */
	public function testExpiredKeyIsRefused(): void
	{
		[$secret, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT, '2020-04-01T12:00:01+00:00');

		// One second before its expiry, read back from the database
		self::assertSame($id, $this->reader()->read($this->request($secret))?->getId()->toString());

		// At its expiry instant
		$this->setExpiry($id, new DateTimeImmutable('2020-04-01T12:00:00+00:00'));
		$this->assertRefused($secret);

		// After it
		$this->setExpiry($id, new DateTimeImmutable('2020-04-01T11:59:59+00:00'));
		$this->assertRefused($secret);
	}

	/**
	 * A key revoked by another process, the fb:api-key:* commands, is refused on the next lookup,
	 * even by a process whose entity manager still holds the key from an earlier one
	 *
	 * @throws Throwable
	 */
	public function testRevocationByAnotherProcessIsSeenWithoutClearing(): void
	{
		[$secret, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$this->auth()->login($this->request($secret));
		self::assertSame(
			self::ADMINISTRATOR_ACCOUNT,
			$this->signedInAs($this->container->getByType(Identity\User::class)),
		);

		// Another process suspends it; this one does not clear its entity manager
		$this->db()->executeStatement(
			'UPDATE fb_security_api_keys SET key_state = ? WHERE key_id = ?',
			[Types\ApiKeyState::SUSPENDED->value, Uuid\Uuid::fromString($id)->getBytes()],
		);

		try {
			$this->auth()->login($this->request($secret));

			self::fail('The suspended key signed the user in');
		} catch (Exceptions\UnauthorizedAccess) {
			self::assertFalse($this->container->getByType(Identity\User::class)->isLoggedIn());
		}
	}

	/**
	 * The key is valid, but its account can no longer sign in (blocked or deleted in the module
	 * that owns accounts): Auth refuses it, and its roles never apply
	 *
	 * @throws Throwable
	 */
	public function testKeyOfAnAccountThatCannotSignInIsRefused(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$this->auth()->login($this->request($secret));
		self::assertTrue($this->container->getByType(Identity\User::class)->isLoggedIn());

		$this->container->getByType(Tests\Support\AccountIdentities::class)->active = [self::USER_ACCOUNT];

		$this->expectException(Exceptions\UnauthorizedAccess::class);

		try {
			$this->auth()->login($this->request($secret));
		} finally {
			self::assertFalse($this->container->getByType(Identity\User::class)->isLoggedIn());
		}
	}

	/**
	 * Fail closed: with no module to resolve accounts, no key signs anyone in
	 *
	 * @throws Throwable
	 */
	public function testKeyIsRefusedWithoutAnAccountProvider(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$auth = new Services\Auth(
			$this->container->getByType(Identity\TokenReader::class),
			$this->container->getByType(Identity\IdentityProvider::class),
			$this->container->getByType(Identity\User::class),
			$this->reader(),
		);

		$this->expectException(Exceptions\UnauthorizedAccess::class);

		$auth->login($this->request($secret));
	}

	/**
	 * @throws Throwable
	 */
	public function testKeyAuthenticatesAsItsAccountWithTheAccountsRoles(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$user = $this->container->getByType(Identity\User::class);

		$this->auth()->login($this->request($secret));

		self::assertTrue($user->isLoggedIn());
		self::assertSame(self::ADMINISTRATOR_ACCOUNT, $this->signedInAs($user));
		self::assertTrue($user->isInRole('administrator'));
		self::assertSame(['administrator'], $user->getRoles());
	}

	/**
	 * @throws Throwable
	 */
	public function testKeyAndTokenForTheSameAccountGetTheSameRoles(): void
	{
		[$secret] = $this->createKey(self::USER_ACCOUNT);

		$user = $this->container->getByType(Identity\User::class);

		$this->auth()->login($this->request($secret));

		$keyRoles = $user->getRoles();

		$this->auth()->login($this->request(null, $this->token(self::USER_ACCOUNT)));

		self::assertSame(self::USER_ACCOUNT, $this->signedInAs($user));
		self::assertSame(['user'], $keyRoles);
		self::assertSame($keyRoles, $user->getRoles());
	}

	/**
	 * @throws Throwable
	 */
	public function testBearerTokenWinsOverApiKey(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$user = $this->container->getByType(Identity\User::class);

		// The user's token decides, not the administrator's key
		$this->auth()->login($this->request($secret, $this->token(self::USER_ACCOUNT)));

		self::assertSame(self::USER_ACCOUNT, $this->signedInAs($user));
		self::assertSame(['user'], $user->getRoles());

		// With a token, the key is not read at all, so a wrong one is no error
		$this->auth()->login($this->request('wrong', $this->token(self::USER_ACCOUNT)));

		self::assertSame(self::USER_ACCOUNT, $this->signedInAs($user));
	}

	/**
	 * @throws Throwable
	 */
	public function testInvalidBearerTokenIsNotRescuedByApiKey(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$this->expectException(Exceptions\UnauthorizedAccess::class);

		$this->auth()->login($this->request($secret, 'not.a.token'));
	}

	/**
	 * @throws Throwable
	 */
	public function testAuthRefusesAnInvalidKey(): void
	{
		$this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$this->expectException(Exceptions\UnauthorizedAccess::class);

		$this->auth()->login($this->request('wrong'));
	}

	/**
	 * @throws Throwable
	 */
	public function testRequestWithNeitherIsAnonymous(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		$user = $this->container->getByType(Identity\User::class);

		$this->auth()->login($this->request($secret));
		self::assertTrue($user->isLoggedIn());

		$this->auth()->login($this->request(null));
		self::assertFalse($user->isLoggedIn());
		self::assertSame(['guest'], $user->getRoles());
	}

	/**
	 * @throws Throwable
	 */
	public function testCreatePrintsTheSecretOnceAndStoresOnlyItsHash(): void
	{
		$tester = new CommandTester($this->container->getByType(Commands\CreateApiKey::class));

		self::assertSame(
			Command::SUCCESS,
			$tester->execute(['--name' => 'Integration', '--account' => self::ADMINISTRATOR_ACCOUNT]),
		);

		$output = $tester->getDisplay();

		self::assertSame(1, preg_match('/^([0-9a-f]{64})$/m', $output, $matches));
		$secret = $matches[1];

		self::assertSame(1, substr_count($output, $secret));
		self::assertFalse(str_contains($output, hash('sha256', $secret)));

		$rows = $this->db()->fetchAllAssociative(
			'SELECT LOWER(HEX(key_id)) AS id, LOWER(HEX(account_id)) AS account, key_name, key_hash, key_state, expires_at FROM fb_security_api_keys',
		);

		self::assertCount(1, $rows);
		self::assertSame(hash('sha256', $secret), $rows[0]['key_hash']);
		self::assertNotSame($secret, $rows[0]['key_hash']);
		self::assertSame('Integration', $rows[0]['key_name']);
		self::assertSame(Uuid\Uuid::fromString(self::ADMINISTRATOR_ACCOUNT)->getHex()->toString(), $rows[0]['account']);
		self::assertSame(Types\ApiKeyState::ACTIVE->value, $rows[0]['key_state']);
		self::assertNull($rows[0]['expires_at']);
		assert(is_string($rows[0]['id']));
		self::assertStringContainsString(Uuid\Uuid::fromString($rows[0]['id'])->toString(), $output);

		// No column holds the secret
		self::assertFalse(
			str_contains(
				var_export($this->db()->fetchAllAssociative('SELECT * FROM fb_security_api_keys'), true),
				$secret,
			),
		);
	}

	/**
	 * @throws Throwable
	 */
	public function testCreateWithQuietPrintsOnlyTheSecret(): void
	{
		$tester = new CommandTester($this->container->getByType(Commands\CreateApiKey::class));

		self::assertSame(
			Command::SUCCESS,
			$tester->execute(
				['--name' => 'Integration', '--account' => self::ADMINISTRATOR_ACCOUNT],
				['verbosity' => OutputInterface::VERBOSITY_QUIET],
			),
		);

		$secret = trim($tester->getDisplay());

		self::assertSame(1, preg_match('/^[0-9a-f]{64}$/', $secret));
		self::assertNotNull($this->reader()->read($this->request($secret)));
	}

	/**
	 * @throws Throwable
	 */
	public function testCreateStoresTheExpiry(): void
	{
		[, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT, '2027-01-01T00:00:00+05:00');

		$this->container->getByType(ORM\EntityManagerInterface::class)->clear();

		$apiKey = $this->container->getByType(Models\ApiKeys\Repository::class)->findOneById(
			Uuid\Uuid::fromString($id),
		);

		self::assertNotNull($apiKey);
		self::assertSame(
			(new DateTimeImmutable('2027-01-01T00:00:00+05:00'))->getTimestamp(),
			$apiKey->getExpiresAt()?->getTimestamp(),
		);
	}

	/**
	 * @return array<string, array{array<string, string>}>
	 */
	public static function invalidCreateInput(): array
	{
		return [
			'no name' => [['--account' => self::ADMINISTRATOR_ACCOUNT]],
			'blank name' => [['--name' => '  ', '--account' => self::ADMINISTRATOR_ACCOUNT]],
			'name too long' => [['--name' => str_repeat('x', 101), '--account' => self::ADMINISTRATOR_ACCOUNT]],
			'no account' => [['--name' => 'Integration']],
			'account is not a UUID' => [['--name' => 'Integration', '--account' => 'john.doe@fastybird.com']],
			'expiry in the past' => [
				[
					'--name' => 'Integration',
					'--account' => self::ADMINISTRATOR_ACCOUNT,
					'--expires-at' => '2020-04-01T11:59:59+00:00',
				],
			],
			'expiry is now' => [
				[
					'--name' => 'Integration',
					'--account' => self::ADMINISTRATOR_ACCOUNT,
					'--expires-at' => '2020-04-01T12:00:00+00:00',
				],
			],
			'unknown account' => [['--name' => 'Integration', '--account' => self::UNKNOWN_ACCOUNT]],
			'expiry is not a date' => [
				['--name' => 'Integration', '--account' => self::ADMINISTRATOR_ACCOUNT, '--expires-at' => 'soon'],
			],
		];
	}

	/**
	 * @param array<string, string> $input
	 *
	 * @throws Throwable
	 */
	#[DataProvider('invalidCreateInput')]
	public function testCreateRefusesInvalidInput(array $input): void
	{
		self::assertSame(Command::FAILURE, $this->execute(Commands\CreateApiKey::class, $input));

		self::assertEquals(0, $this->db()->fetchOne('SELECT COUNT(*) FROM fb_security_api_keys'));
	}

	/**
	 * @throws Throwable
	 */
	public function testCreateFailureLogsNeitherTheSecretNorTheHash(): void
	{
		// The insert fails
		$this->db()->executeStatement('DROP TABLE fb_security_api_keys');

		$context = null;

		$logger = $this->createMock(Log\LoggerInterface::class);
		$logger
			->expects(self::once())
			->method('error')
			->willReturnCallback(static function (string $message, array $logContext) use (&$context): void {
				$context = $logContext;
			});

		$tester = new CommandTester(new Commands\CreateApiKey(
			$this->container->getByType(Models\ApiKeys\Manager::class),
			$this->container->getByType(ClockInterface::class),
			$this->container->getByType(Identity\AccountIdentityProvider::class),
			$logger,
		));

		self::assertSame(
			Command::FAILURE,
			$tester->execute(['--name' => 'Integration', '--account' => self::ADMINISTRATOR_ACCOUNT]),
		);

		self::assertNotNull($context);
		self::assertSame(0, preg_match('/[0-9a-f]{64}/', $tester->getDisplay()));
		self::assertSame(0, preg_match('/[0-9a-f]{64}/', (string) json_encode($context)));
	}

	/**
	 * @throws Throwable
	 */
	public function testListShowsKeysButNeverTheirSecretsOrHashes(): void
	{
		[$activeSecret, $active] = $this->createKey(self::ADMINISTRATOR_ACCOUNT, null, 'Active');
		[$suspendedSecret, $suspended] = $this->createKey(self::USER_ACCOUNT, null, 'Suspended');
		[$expiredSecret, $expired] = $this->createKey(
			self::ADMINISTRATOR_ACCOUNT,
			'2020-04-01T12:00:01+00:00',
			'Expired',
		);

		self::assertSame(Command::SUCCESS, $this->execute(Commands\SuspendApiKey::class, ['id' => $suspended]));
		$this->setExpiry($expired, new DateTimeImmutable('2020-04-01T11:00:00+00:00'));

		$tester = new CommandTester($this->container->getByType(Commands\ListApiKeys::class));

		self::assertSame(Command::SUCCESS, $tester->execute([]));

		$output = $tester->getDisplay();

		self::assertSame(
			1,
			preg_match('/' . $active . '\s+Active\s+' . self::ADMINISTRATOR_ACCOUNT . '\s+active\s/', $output),
		);
		self::assertSame(
			1,
			preg_match('/' . $suspended . '\s+Suspended\s+' . self::USER_ACCOUNT . '\s+suspended\s/', $output),
		);
		self::assertSame(
			1,
			preg_match('/' . $expired . '\s+Expired\s+' . self::ADMINISTRATOR_ACCOUNT . '\s+expired\s/', $output),
		);

		foreach ([$activeSecret, $suspendedSecret, $expiredSecret] as $secret) {
			self::assertFalse(str_contains($output, $secret));
			self::assertFalse(str_contains($output, hash('sha256', $secret)));
		}
	}

	/**
	 * @throws Throwable
	 */
	public function testSuspendRefusesADeletedKey(): void
	{
		[, $id] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		self::assertSame(Command::SUCCESS, $this->execute(Commands\DeleteApiKey::class, ['id' => $id]));
		self::assertSame(Command::FAILURE, $this->execute(Commands\SuspendApiKey::class, ['id' => $id]));

		$this->assertState($id, Types\ApiKeyState::DELETED);
	}

	/**
	 * @throws Throwable
	 */
	public function testSuspendAndDeleteRefuseAnUnknownOrInvalidIdentifier(): void
	{
		[$secret] = $this->createKey(self::ADMINISTRATOR_ACCOUNT);

		foreach ([Commands\SuspendApiKey::class, Commands\DeleteApiKey::class] as $command) {
			self::assertSame(Command::FAILURE, $this->execute($command, ['id' => self::UNKNOWN_KEY]));
			self::assertSame(Command::FAILURE, $this->execute($command, ['id' => 'not-a-uuid']));
		}

		self::assertNotNull($this->reader()->read($this->request($secret)));
	}

	/**
	 * @return array{non-empty-string, string} the secret and the key's identifier
	 *
	 * @throws Throwable
	 */
	private function createKey(string $account, string|null $expiresAt = null, string $name = 'Integration'): array
	{
		$input = ['--name' => $name, '--account' => $account];

		if ($expiresAt !== null) {
			$input['--expires-at'] = $expiresAt;
		}

		$tester = new CommandTester($this->container->getByType(Commands\CreateApiKey::class));

		self::assertSame(Command::SUCCESS, $tester->execute($input));
		self::assertSame(1, preg_match('/^([0-9a-f]{64})$/m', $tester->getDisplay(), $matches));

		$ids = array_column(
			$this->db()->fetchAllAssociative(
				'SELECT LOWER(HEX(key_id)) AS id FROM fb_security_api_keys WHERE key_hash = ?',
				[hash('sha256', $matches[1])],
			),
			'id',
		);

		self::assertCount(1, $ids);
		assert(is_string($ids[0]));

		return [$matches[1], Uuid\Uuid::fromString($ids[0])->toString()];
	}

	/**
	 * @param class-string<Command> $type
	 * @param array<string, string> $input
	 *
	 * @throws Throwable
	 */
	private function execute(string $type, array $input): int
	{
		$command = $this->container->getByType($type);
		assert($command instanceof Command);

		return (new CommandTester($command))->execute($input);
	}

	/**
	 * @throws Throwable
	 */
	private function setExpiry(string $id, DateTimeImmutable $expiresAt): void
	{
		$repository = $this->container->getByType(Models\ApiKeys\Repository::class);

		$apiKey = $repository->findOneById(Uuid\Uuid::fromString($id));
		assert($apiKey !== null);

		$this->container->getByType(Models\ApiKeys\Manager::class)->update(
			$apiKey,
			Utils\ArrayHash::from(['expiresAt' => $expiresAt]),
		);

		// Read the key back from the database, not from the identity map
		$this->container->getByType(ORM\EntityManagerInterface::class)->clear();
	}

	/**
	 * @throws Throwable
	 */
	private function assertState(string $id, Types\ApiKeyState $state): void
	{
		self::assertSame(
			$state->value,
			$this->db()->fetchOne(
				'SELECT key_state FROM fb_security_api_keys WHERE key_id = ?',
				[Uuid\Uuid::fromString($id)->getBytes()],
			),
		);
	}

	/**
	 * @throws Throwable
	 */
	private function assertRefused(string $secret): void
	{
		$this->container->getByType(ORM\EntityManagerInterface::class)->clear();

		try {
			$this->reader()->read($this->request($secret));

			self::fail('The key was accepted');
		} catch (Exceptions\UnauthorizedAccess) {
			// Refused, as expected
		}

		try {
			$this->auth()->login($this->request($secret));

			self::fail('The key signed the user in');
		} catch (Exceptions\UnauthorizedAccess) {
			self::assertFalse($this->container->getByType(Identity\User::class)->isLoggedIn());
		}
	}

	/**
	 * @throws Throwable
	 */
	private function request(string|null $apiKey, string|null $token = null): ServerRequest
	{
		$headers = [];

		if ($apiKey !== null) {
			$headers['X-Api-Key'] = $apiKey;
		}

		if ($token !== null) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		return new ServerRequest('GET', '/api/v1/devices', $headers);
	}

	/**
	 * @throws Throwable
	 */
	private function token(string $account): string
	{
		return $this->container->getByType(Identity\TokenBuilder::class)->build($account, [])->toString();
	}

	private function signedInAs(Identity\User $user): string|null
	{
		return $user->getId()?->toString();
	}

	/**
	 * @throws Throwable
	 */
	private function reader(): Identity\ApiKeyReader
	{
		return $this->container->getByType(Identity\ApiKeyReader::class);
	}

	/**
	 * @throws Throwable
	 */
	private function auth(): Services\Auth
	{
		return $this->container->getByType(Services\Auth::class);
	}

	/**
	 * @throws Throwable
	 */
	private function db(): DBAL\Connection
	{
		return $this->container->getByType(DBAL\Connection::class);
	}

}
