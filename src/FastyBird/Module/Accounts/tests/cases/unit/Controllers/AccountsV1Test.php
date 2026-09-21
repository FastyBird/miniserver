<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Http\SlimRouter as SlimRouterHttp;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Module\Accounts\Exceptions;
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
use React\Http\Message\ServerRequest;
use RuntimeException;
use function file_get_contents;
use function is_array;
use function str_replace;
use function strval;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccountsV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const ADMINISTRATOR_ACCOUNT_ID = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const CHILD_USER_ACCOUNT_ID = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	private const USER_ACCOUNT_ID = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	private const UNKNOWN_ID = '83985c13-238c-46bd-aacb-2359d5c921a7';

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountsRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouterRouting\IRouter::class);

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

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function accountsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.index.json',
			],
			'readAllPaging' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts?page[offset]=1&page[limit]=1',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.index.paging.json',
			],
			'readOneUser' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.read.user.json',
			],
			'readRelationshipsIdentities' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.relationships.identities.json',
			],
			'readRelationshipsRoles' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_ROLES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.relationships.roles.json',
			],
			'readRelationshipsEmails' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.relationships.emails.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownEntity' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::UNKNOWN_ID . '/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllNoToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllUserToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountsCreate')]
	public function testCreate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouterRouting\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			$headers,
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());

		$responseBody = (string) $response->getBody();

		$actual = Utils\Json::decode($responseBody, forceArrays: true);
		self::assertTrue(is_array($actual));

		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			$responseBody,
			static function (string $expectation) use ($actual): string {
				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['attributes'])
					&& is_array($actual['data']['attributes'])
					&& isset($actual['data']['attributes']['registered'])
				) {
					$expectation = str_replace(
						'__TIME_PLACEHOLDER__',
						strval($actual['data']['attributes']['registered']),
						$expectation,
					);
				}

				return $expectation;
			},
		);
	}

	/**
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function accountsCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'createUser' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.user.json',
				),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.create.user.json',
			],
			'createUserWithRoles' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.userWithRoles.json',
				),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.create.userWithRoles.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.create.missing.required.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'identifierNotUnique' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.identifier.notUnique.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/identifier.notUnique.json',
			],
			// User role could not be combined with other roles
			'invalidRoles' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.invalid.roles.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.invalid.role.json',
			],
			'noToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.user.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.user.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.user.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.user.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.create.user.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountsUpdate')]
	public function testUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouterRouting\IRouter::class);

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

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function accountsUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'updateUser' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.user.json',
				),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.update.user.json',
			],
			'updateUserWithRoles' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::CHILD_USER_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.userWithRoles.json',
				),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.update.userWithRoles.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'invalidRolesCombination' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::CHILD_USER_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.invalid.rolesCombination.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.invalid.role.json',
			],
			'noToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.user.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.user.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.user.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.user.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/accounts/accounts.update.user.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountsDelete')]
	public function testDelete(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouterRouting\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function accountsDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::USER_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.delete.json',
			],

			// Invalid responses
			////////////////////
			'self' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/accounts/accounts.delete.self.json',
			],
			'unknown' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'noToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
