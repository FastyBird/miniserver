<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Subscribers;

use DateTimeImmutable;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Tests;
use FastyBird\Module\Accounts\Types;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use Throwable;

/**
 * An account that may no longer sign in loses its access and refresh tokens, whichever way it got
 * there: its state, or the state of one of its identities, is changed to anything but active, or
 * it is deleted. The tokens are deleted, as a sign-out deletes them, so making the account active
 * again does not bring them back. The rule is kept by the module's entity subscriber, so the HTTP
 * API, the console and any other writer through the entity manager follow it alike.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccountTokensTest extends Tests\Cases\Unit\DbTestCase
{

	// The account of ADMINISTRATOR_TOKEN and its identity, and the account of USER_TOKEN (sql/dummy.data.sql)
	private const string ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string IDENTITY = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	private const string USER_ACCOUNT = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	private const string SESSION = '/api/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session';

	private const string ACCOUNTS = '/api/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/';

	/**
	 * @return array<string, array{Types\AccountState}>
	 */
	public static function accountStatesThatCannotSignIn(): array
	{
		return [
			'blocked' => [Types\AccountState::BLOCKED],
			'deleted' => [Types\AccountState::DELETED],
			'not activated' => [Types\AccountState::NOT_ACTIVATED],
			'approval waiting' => [Types\AccountState::APPROVAL_WAITING],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('accountStatesThatCannotSignIn')]
	public function testAnAccountThatCannotSignInLosesItsTokens(Types\AccountState $state): void
	{
		$this->updateAccount(self::ACCOUNT, ['state' => $state]);

		self::assertSame(0, $this->tokensOf(self::ACCOUNT));

		// The other account keeps its access token and its refresh token, and nothing else is left
		self::assertSame(2, $this->tokensOf(self::USER_ACCOUNT));
		self::assertSame(2, $this->allTokens());
	}

	/**
	 * @throws Throwable
	 */
	public function testABlockedAccountMadeActiveAgainDoesNotGetItsTokensBack(): void
	{
		self::assertSame(StatusCodeInterface::STATUS_OK, $this->readSession());

		$this->updateAccount(self::ACCOUNT, ['state' => Types\AccountState::BLOCKED]);
		$this->updateAccount(self::ACCOUNT, ['state' => Types\AccountState::ACTIVE]);

		self::assertSame(0, $this->tokensOf(self::ACCOUNT));
		self::assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $this->readSession());
	}

	/**
	 * Signing in writes the account's last visit; an update that leaves the account active keeps
	 * its tokens
	 *
	 * @throws Throwable
	 */
	public function testAnUpdateThatKeepsTheAccountActiveKeepsItsTokens(): void
	{
		$this->updateAccount(self::ACCOUNT, ['lastVisit' => new DateTimeImmutable('2020-04-01T12:00:00+00:00')]);
		$this->updateAccount(self::ACCOUNT, ['state' => Types\AccountState::ACTIVE]);

		self::assertSame(2, $this->tokensOf(self::ACCOUNT));
		self::assertSame(StatusCodeInterface::STATUS_OK, $this->readSession());
	}

	/**
	 * An account becoming active keeps whatever tokens it has
	 *
	 * @throws Throwable
	 */
	public function testAnAccountMadeActiveKeepsItsTokens(): void
	{
		// Written by another process, which leaves the tokens alone
		$this->getDb()->executeStatement(
			'UPDATE fb_accounts_module_accounts SET account_state = ? WHERE account_id = ?',
			[Types\AccountState::APPROVAL_WAITING->value, Uuid\Uuid::fromString(self::ACCOUNT)->getBytes()],
		);

		$this->updateAccount(self::ACCOUNT, ['state' => Types\AccountState::ACTIVE]);

		self::assertSame(2, $this->tokensOf(self::ACCOUNT));
		self::assertSame(StatusCodeInterface::STATUS_OK, $this->readSession());
	}

	/**
	 * @return array<string, array{Types\IdentityState}>
	 */
	public static function identityStatesThatCannotSignIn(): array
	{
		return [
			'blocked' => [Types\IdentityState::BLOCKED],
			'deleted' => [Types\IdentityState::DELETED],
			'invalid' => [Types\IdentityState::INVALID],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('identityStatesThatCannotSignIn')]
	public function testAnIdentityThatCannotSignInLosesItsTokens(Types\IdentityState $state): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesRepository::class);

		$findIdentity = new Queries\Entities\FindIdentities();
		$findIdentity->byId(Uuid\Uuid::fromString(self::IDENTITY));

		$identity = $repository->findOneBy($findIdentity);
		self::assertNotNull($identity);

		$this->getContainer()->getByType(Models\Entities\Identities\IdentitiesManager::class)
			->update($identity, Utils\ArrayHash::from(['state' => $state]));

		self::assertSame(0, $this->tokensOf(self::ACCOUNT));
		self::assertSame(2, $this->tokensOf(self::USER_ACCOUNT));
		self::assertSame(2, $this->allTokens());
	}

	/**
	 * The API deletes an account by switching it and its identities to deleted
	 *
	 * @throws Throwable
	 */
	public function testAnAccountDeletedThroughTheApiLosesItsTokens(): void
	{
		$response = $this->getContainer()->getByType(Routing\Router::class)->handle(new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			self::ACCOUNTS . self::USER_ACCOUNT,
			['authorization' => 'Bearer ' . self::ADMINISTRATOR_TOKEN],
		));

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());

		self::assertSame(0, $this->tokensOf(self::USER_ACCOUNT));
		self::assertSame(2, $this->tokensOf(self::ACCOUNT));
		self::assertSame(2, $this->allTokens());
	}

	/**
	 * Removed from the database, the account takes its access tokens with it through the foreign
	 * keys; its refresh tokens have to be removed as well
	 *
	 * @throws Throwable
	 */
	public function testARemovedAccountLeavesNoTokenBehind(): void
	{
		$this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class)
			->delete($this->findAccount(self::USER_ACCOUNT));

		self::assertSame(2, $this->allTokens());
		self::assertSame(2, $this->tokensOf(self::ACCOUNT));
	}

	/**
	 * @param array<string, mixed> $values
	 *
	 * @throws Throwable
	 */
	private function updateAccount(string $id, array $values): void
	{
		$this->getContainer()->getByType(Models\Entities\Accounts\AccountsManager::class)
			->update($this->findAccount($id), Utils\ArrayHash::from($values));
	}

	/**
	 * @throws Throwable
	 */
	private function findAccount(string $id): Entities\Accounts\Account
	{
		$findAccount = new Queries\Entities\FindAccounts();
		$findAccount->byId(Uuid\Uuid::fromString($id));

		$account = $this->getContainer()->getByType(Models\Entities\Accounts\AccountsRepository::class)
			->findOneBy($findAccount);

		self::assertNotNull($account);

		return $account;
	}

	/**
	 * The account's access tokens, and the refresh tokens issued with them
	 *
	 * @throws Throwable
	 */
	private function tokensOf(string $account): int
	{
		$count = $this->getDb()->fetchOne(
			'SELECT COUNT(*) FROM fb_security_tokens t'
			. ' LEFT JOIN fb_security_tokens a ON a.token_id = t.parent_id'
			. ' JOIN fb_accounts_module_identities i ON i.identity_id = COALESCE(t.identity_id, a.identity_id)'
			. ' WHERE i.account_id = ?',
			[Uuid\Uuid::fromString($account)->getBytes()],
		);

		self::assertIsNumeric($count);

		return (int) $count;
	}

	/**
	 * Every token, including a refresh token whose access token is gone
	 *
	 * @throws Throwable
	 */
	private function allTokens(): int
	{
		$count = $this->getDb()->fetchOne('SELECT COUNT(*) FROM fb_security_tokens');
		self::assertIsNumeric($count);

		return (int) $count;
	}

	/**
	 * @throws Throwable
	 */
	private function readSession(): int
	{
		$response = $this->getContainer()->getByType(Routing\Router::class)->handle(new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			self::SESSION,
			['authorization' => 'Bearer ' . self::ADMINISTRATOR_TOKEN],
		));

		self::assertTrue($response instanceof Http\Response);

		return $response->getStatusCode();
	}

}
