<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\SlimRouter;
use IPub\SlimRouter\Http as SlimRouterHttp;
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
final class EmailsV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const ADMINISTRATOR_ACCOUNT_ID = '5e79efbf-bd0d-5b7c-46ef-bfbdefbfbd34';

	private const USER_ACCOUNT_ID = 'efbfbdef-bfbd-68ef-bfbd-770b40efbfbd';

	private const USER_ACCOUNT_ID_2 = 'fae8d781-7e2c-4318-9c85-43ba637d14c5';

	private const ADMINISTRATOR_EMAIL_ID = '32ebe3c3-0238-482e-ab79-6b1d9ee2147c';

	private const USER_EMAIL_ID = '73efbfbd-efbf-bd36-44ef-bfbdefbfbd7a';

	private const USER_NOT_VERIFIED_EMAIL_ID = 'ed987404-f14c-40b4-9150-15b6590deb8c';

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
	#[DataProvider('emailsRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

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
	public static function emailsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::USER_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.index.json',
			],
			'readAllPaging' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails?page[offset]=1&page[limit]=1',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.index.paging.json',
			],
			'readOne' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.read.json',
			],
			'readRelationshipsAccount' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.relationships.account.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readAllNoToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneNoToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllUserToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneUserToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
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
	#[DataProvider('emailsCreate')]
	public function testCreate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

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
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function emailsCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'create' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.missing.required.json',
			],
			'missingRelation' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.missing.relation.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.missing.relation.json',
			],
			'invalidRelation' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::USER_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.invalid.relation.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'identifierNotUnique' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.identifier.notUnique.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/identifier.notUnique.json',
			],
			'invalidEmail' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.invalid.email.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.invalid.email.json',
			],
			'usedEmail' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.usedEmail.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.usedEmail.json',
			],
			'noToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.create.json'),
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
	#[DataProvider('emailsUpdate')]
	public function testUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

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
	public static function emailsUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.update.json',
			],
			'verify' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::USER_ACCOUNT_ID_2 . '/emails/' . self::USER_NOT_VERIFIED_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.verify.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.verify.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidRelation' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.invalid.relation.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.update.invalid.relation.json',
			],
			'invalidType' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'noToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/emails/emails.update.json'),
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
	#[DataProvider('emailsDelete')]
	public function testDelete(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

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
	public static function emailsDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.delete.json',
			],

			// Invalid responses
			////////////////////
			'default' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::USER_ACCOUNT_ID . '/emails/' . self::USER_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/emails/emails.delete.default.json',
			],
			'unknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'noToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/accounts/' . self::ADMINISTRATOR_ACCOUNT_ID . '/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
