<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use Doctrine\DBAL;
use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Identity;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use RuntimeException;
use function file_get_contents;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccountV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const USER_ACCOUNT_ID = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function accountRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'read' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.json',
			],
			'readUser' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.user.json',
			],
			'readWithIncluded' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me?include=emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.included.json',
			],
			'readRelationshipsEmails' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.emails.json',
			],
			'readRelationshipsIdentities' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.identities.json',
			],
			'readRelationshipsRoles' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_ROLES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.roles.json',
			],

			// Invalid responses
			////////////////////
			'readRelationshipsUnknown' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readNoToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readEmptyToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readExpiredToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readInvalidToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsNoToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsEmptyToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsInvalidToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsExpiredToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountUpdate')]
	public function testUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			$url,
			$headers,
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function accountUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.update.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.update.missing.required.json',
			],
			'invalidType' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'noToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * PATCH /v1/me is self-service, so a roles relationship in the request must not change the
	 * caller's roles. Roles are assigned only by AccountsV1::assignAccountToRoles().
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function testUpdateIgnoresRoles(): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
			[
				'authorization' => 'Bearer ' . self::USER_TOKEN,
			],
			Utils\FileSystem::read(
				__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.roles.json',
			),
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

		$enforcer = $this->getContainer()->getByType(Identity\EnforcerFactory::class)->getEnforcer();

		// Read the stored policy, not the enforcer's in-memory copy
		$enforcer->loadPolicy();

		self::assertSame(['user'], $enforcer->getRolesForUser(self::USER_ACCOUNT_ID));
	}

	/**
	 * TAccount::validateDetailsAttribute() only validates; the stored names come from the nested
	 * `details` object. Read back from the table, not from the entity manager.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 * @throws DBAL\Exception
	 */
	public function testUpdatePersistsDetails(): void
	{
		$response = $this->updateDetails([
			'first_name' => 'Janet',
			'last_name' => 'Smithers',
			'middle_name' => 'Quinn',
		]);

		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
		self::assertSame(
			[
				'detail_first_name' => 'Janet',
				'detail_last_name' => 'Smithers',
				'detail_middle_name' => 'Quinn',
			],
			$this->storedDetails(),
		);
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function incompleteDetails(): array
	{
		return [
			'missing first_name' => ['last_name', '/data/attributes/details/first_name'],
			'missing last_name' => ['first_name', '/data/attributes/details/last_name'],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 * @throws DBAL\Exception
	 */
	#[DataProvider('incompleteDetails')]
	public function testUpdateRejectsIncompleteDetails(string $sent, string $pointer): void
	{
		$response = $this->updateDetails([$sent => 'Janet']);

		self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $response->getStatusCode());

		$body = Utils\Json::decode((string) $response->getBody(), forceArrays: true);

		self::assertIsArray($body);
		self::assertSame(
			[
				[
					'status' => '422',
					'code' => '422',
					'title' => 'Missing attribute',
					'detail' => 'Provided request is missing required attribute',
					'source' => ['pointer' => $pointer],
				],
			],
			$body['errors'] ?? null,
		);
		self::assertSame(
			[
				'detail_first_name' => 'Jane',
				'detail_last_name' => 'Doe',
				'detail_middle_name' => null,
			],
			$this->storedDetails(),
		);
	}

	/**
	 * @param array<string, string> $details
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	private function updateDetails(array $details): Http\Response
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			'/api/' . \FastyBird\Module\Accounts\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
			[
				'authorization' => 'Bearer ' . self::USER_TOKEN,
			],
			Utils\Json::encode([
				'data' => [
					'type' => 'com.fastybird.accounts-module/account',
					'id' => self::USER_ACCOUNT_ID,
					'attributes' => [
						'details' => $details,
					],
				],
			]),
		));

		self::assertTrue($response instanceof Http\Response);

		return $response;
	}

	/**
	 * @return array<string, mixed>|false
	 *
	 * @throws AccountsExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws DBAL\Exception
	 */
	private function storedDetails(): array|false
	{
		return $this->getDb()->fetchAssociative(
			'SELECT detail_first_name, detail_last_name, detail_middle_name'
			. ' FROM fb_accounts_module_accounts_details WHERE account_id = :account',
			['account' => Uuid\Uuid::fromString(self::USER_ACCOUNT_ID)->getBytes()],
		);
	}

}
