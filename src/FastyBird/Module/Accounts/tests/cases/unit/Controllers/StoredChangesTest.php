<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use DateTimeInterface;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Types as SecurityTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Tests;
use FastyBird\Module\Accounts\Types as AccountsTypes;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use Throwable;
use function assert;
use function is_array;
use function is_string;

/**
 * What the Accounts controllers store when they change an account, an identity or a session
 * themselves, rather than from a hydrated request document (#723).
 *
 * The controllers build these changes as plain arrays since #723 and as ArrayHash before; these
 * tests pin, field by field, what reaches the database, and pass on both.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoredChangesTest extends Tests\Cases\Unit\DbTestCase
{

	private const string ADMINISTRATOR_ACCOUNT = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const string ADMINISTRATOR_IDENTITY = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	private const string USER_ACCOUNT = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	private const string NOT_ACTIVATED_ACCOUNT = 'fd23ccb4-8d87-4eb3-94da-638ab4e10ae3';

	// What the fixtures store for every account: a key that expired in 2019
	private const string EXPIRED_REQUEST_HASH = 'YjRqZXFoZGw1Z3ZzIyMxNTc0MjA3NDQ1';

	private const string ADMINISTRATOR_REQUEST_HASH = 'NGZqMmVxdnhubjJpIyMxNTc0NDUwNDAz';

	// The test container's frozen clock (12:00 UTC) plus each token's lifetime. The column keeps
	// the wall time without a zone, and it is read back in the container's Europe/Prague.
	private const string ACCESS_TOKEN_VALID_TILL = '2020-04-01T18:00:00+02:00';

	private const string REFRESH_TOKEN_VALID_TILL = '2020-04-04T12:00:00+02:00';

	private const string FIXTURES = __DIR__ . '/../../../fixtures/Controllers/requests/';

	/**
	 * AccountIdentitiesV1 stores the new password, and only it
	 *
	 * @throws Throwable
	 */
	public function testAPasswordChangeStoresTheNewPassword(): void
	{
		$response = $this->request(
			RequestMethodInterface::METHOD_PATCH,
			'/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY,
			Utils\FileSystem::read(self::FIXTURES . 'account/identities/account.identities.update.json'),
			'Bearer ' . self::ADMINISTRATOR_TOKEN,
		);

		self::assertSame(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());

		$identity = $this->find(Entities\Identities\Identity::class, self::ADMINISTRATOR_IDENTITY);

		self::assertTrue($identity->verifyPassword('987654321'));
		self::assertFalse($identity->verifyPassword('123456789'));
		self::assertSame('john.doe@fastybird.com', $identity->getUid());
		self::assertSame(AccountsTypes\IdentityState::ACTIVE, $identity->getState());
	}

	/**
	 * PublicV1 stores a new, valid request hash for an active account
	 *
	 * @throws Throwable
	 */
	public function testAResetRequestStoresANewRequestHash(): void
	{
		$response = $this->request(
			RequestMethodInterface::METHOD_POST,
			'/v1/reset-identity',
			Utils\FileSystem::read(self::FIXTURES . 'public/account.identities.passwordRequest.json'),
		);

		self::assertSame(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());

		$account = $this->find(Entities\Accounts\Account::class, self::ADMINISTRATOR_ACCOUNT);

		$this->assertANewRequestHash($account->getRequestHash(), self::ADMINISTRATOR_REQUEST_HASH);
		self::assertSame(AccountsTypes\AccountState::ACTIVE, $account->getState());
	}

	/**
	 * PublicV1 refuses to reset the identity of an account that was never activated, and replaces
	 * its expired request hash on the way
	 *
	 * @throws Throwable
	 */
	public function testAResetRequestRenewsTheExpiredRequestHashOfANotActivatedAccount(): void
	{
		$response = $this->request(
			RequestMethodInterface::METHOD_POST,
			'/v1/reset-identity',
			Utils\FileSystem::read(self::FIXTURES . 'public/account.identities.passwordRequest.notActivated.json'),
		);

		self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());

		$account = $this->find(Entities\Accounts\Account::class, self::NOT_ACTIVATED_ACCOUNT);

		$this->assertANewRequestHash($account->getRequestHash(), self::EXPIRED_REQUEST_HASH);
		self::assertSame(AccountsTypes\AccountState::NOT_ACTIVATED, $account->getState());
	}

	/**
	 * AccountsV1 marks the account and every one of its identities deleted
	 *
	 * @throws Throwable
	 */
	public function testDeletingAnAccountMarksItAndItsIdentitiesDeleted(): void
	{
		$response = $this->request(
			RequestMethodInterface::METHOD_DELETE,
			'/v1/accounts/' . self::USER_ACCOUNT,
			null,
			'Bearer ' . self::ADMINISTRATOR_TOKEN,
		);

		self::assertSame(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());

		$account = $this->find(Entities\Accounts\Account::class, self::USER_ACCOUNT);

		self::assertSame(AccountsTypes\AccountState::DELETED, $account->getState());
		self::assertNotSame([], $account->getIdentities());

		foreach ($account->getIdentities() as $identity) {
			self::assertSame(AccountsTypes\IdentityState::DELETED, $identity->getState());
		}
	}

	/**
	 * SessionV1 stores an access token for the identity that signed in, and a refresh token that
	 * belongs to it
	 *
	 * @throws Throwable
	 */
	public function testSigningInStoresAnAccessAndARefreshToken(): void
	{
		$response = $this->request(
			RequestMethodInterface::METHOD_POST,
			'/v1/session',
			Utils\FileSystem::read(self::FIXTURES . 'session/session.create.json'),
		);

		self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());

		$this->assertTheSessionTokens($response);
	}

	/**
	 * SessionV1 stores a new access and refresh token pair in place of the refreshed one
	 *
	 * @throws Throwable
	 */
	public function testRefreshingASessionStoresANewAccessAndRefreshToken(): void
	{
		$response = $this->request(
			RequestMethodInterface::METHOD_PATCH,
			'/v1/session',
			Utils\FileSystem::read(self::FIXTURES . 'session/session.update.json'),
		);

		self::assertSame(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());

		$this->assertTheSessionTokens($response);
	}

	/**
	 * @throws Throwable
	 */
	private function assertTheSessionTokens(Http\Response $response): void
	{
		$body = Utils\Json::decode((string) $response->getBody(), forceArrays: true);
		assert(is_array($body) && is_array($body['data'] ?? null) && is_array($body['data']['attributes'] ?? null));

		$attributes = $body['data']['attributes'];
		assert(is_string($attributes['token'] ?? null) && is_string($attributes['refresh'] ?? null));

		$this->getEntityManager()->clear();

		$accessToken = $this->getEntityManager()
			->getRepository(Entities\Tokens\AccessToken::class)
			->findOneBy(['token' => $attributes['token']]);
		self::assertInstanceOf(Entities\Tokens\AccessToken::class, $accessToken);

		self::assertSame($body['data']['id'] ?? null, $accessToken->getId()->toString());
		self::assertSame(SecurityTypes\TokenState::ACTIVE, $accessToken->getState());
		self::assertSame(
			self::ACCESS_TOKEN_VALID_TILL,
			$accessToken->getValidTill()?->format(DateTimeInterface::ATOM),
		);
		self::assertSame(self::ADMINISTRATOR_IDENTITY, $accessToken->getIdentity()->getId()->toString());
		self::assertNull($accessToken->getParent());

		$refreshToken = $this->getEntityManager()
			->getRepository(Entities\Tokens\RefreshToken::class)
			->findOneBy(['token' => $attributes['refresh']]);
		self::assertInstanceOf(Entities\Tokens\RefreshToken::class, $refreshToken);

		self::assertSame(SecurityTypes\TokenState::ACTIVE, $refreshToken->getState());
		self::assertSame(
			self::REFRESH_TOKEN_VALID_TILL,
			$refreshToken->getValidTill()?->format(DateTimeInterface::ATOM),
		);
		self::assertSame($accessToken->getId()->toString(), $refreshToken->getParent()?->getId()->toString());
	}

	/**
	 * @throws Throwable
	 */
	private function assertANewRequestHash(string|null $stored, string $before): void
	{
		self::assertIsString($stored);
		self::assertNotSame($before, $stored);
		self::assertTrue($this->getContainer()->getByType(Helpers\SecurityHash::class)->isValid($stored));
	}

	/**
	 * @throws Throwable
	 */
	private function request(
		string $method,
		string $path,
		string|null $body,
		string|null $authorization = null,
	): Http\Response
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(new ServerRequest(
			$method,
			'/api/' . Accounts\Constants::MODULE_ACCOUNTS_PREFIX . $path,
			$authorization !== null ? ['authorization' => $authorization] : [],
			$body ?? '',
		));
		self::assertInstanceOf(Http\Response::class, $response);

		return $response;
	}

	/**
	 * Read back from the database, not from what the request left in memory
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 *
	 * @return T
	 *
	 * @throws Throwable
	 */
	private function find(string $class, string $id): object
	{
		$this->getEntityManager()->clear();

		$entity = $this->getEntityManager()->find($class, Uuid\Uuid::fromString($id));
		assert($entity instanceof $class);

		return $entity;
	}

}
