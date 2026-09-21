<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants\Metadata;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Http\SlimRouter as SlimRouterHttp;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Module\Triggers\Tests;
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
final class ActionsV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('actionsRead')]
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
	public static function actionsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.index.json',
			],
			'readAllPaging' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.index.paging.json',
			],
			'readOne' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.read.json',
			],
			'readOneInclude' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4?include=trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.read.include.json',
			],
			'readRelationshipsTrigger' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4/relationships/trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.readRelationships.trigger.json',
			],
			'readAllUser' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::VALID_TOKEN_USER,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.index.json',
			],
			'readOneUser' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN_USER,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.read.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readOneUnknownTrigger' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownAction' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888/relationships/trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknownTrigger' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888/actions/4aa84028-d8b7-4128-95b2-295763634aa4/relationships/trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllMissingToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('actionsCreate')]
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
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function actionsCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'createChannelProperty' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.create.json',
			],

			// Invalid responses
			////////////////////
			'notAllowed' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.create.missing.required.json',
			],
			'unknownTrigger' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888/actions',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'missingToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('actionsUpdate')]
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
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function actionsUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.update.json',
			],

			// Invalid responses
			////////////////////
			'notAllowed' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'unknownTrigger' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'unknownAction' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.idMismatch.json'),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'missingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/actions.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('actionsDelete')]
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
	public static function actionsDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/actions.delete.json',
			],

			// Invalid responses
			////////////////////
			'notAllowed' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN_USER,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'deleteUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'unknownTrigger' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/74e40f3e-84cb-4e0c-b3b3-fbf8246e0888/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'missingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_TRIGGERS_PREFIX . '/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/actions/4aa84028-d8b7-4128-95b2-295763634aa4',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
