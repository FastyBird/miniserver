<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants as Metadata;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Http\SlimRouter as SlimRouterHttp;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Module\Accounts\Exceptions;
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
final class PublicV1Test extends Tests\Cases\Unit\DbTestCase
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
	#[DataProvider('resetIdentity')]
	public function testResetIdentity(string $url, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouterRouting\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			[],
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
	public static function resetIdentity(): array
	{
		return [
			// Valid responses
			//////////////////
			'request' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.json',
				),
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.missing.required.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.invalidType.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'unknown' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.invalid.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'deleted' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.deleted.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'blocked' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.blocked.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.blocked.json',
			],
			'notActivated' => [
				'/api/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.notActivated.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.notActivated.json',
			],
		];
	}

}
