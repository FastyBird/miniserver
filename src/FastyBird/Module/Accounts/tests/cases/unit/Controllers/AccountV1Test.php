<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants\Metadata;
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

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class AccountV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('accountRead')]
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
	public static function accountRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'read' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.json',
			],
			'readUser' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.user.json',
			],
			'readWithIncluded' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me?include=emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.included.json',
			],
			'readRelationshipsEmails' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.emails.json',
			],
			'readRelationshipsIdentities' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.identities.json',
			],
			'readRelationshipsRoles' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_ROLES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.roles.json',
			],

			// Invalid responses
			////////////////////
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readNoToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readEmptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readExpiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readInvalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsNoToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsEmptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsInvalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsExpiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
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
	#[DataProvider('accountUpdate')]
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
	public static function accountUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.update.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.update.missing.required.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'noToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
