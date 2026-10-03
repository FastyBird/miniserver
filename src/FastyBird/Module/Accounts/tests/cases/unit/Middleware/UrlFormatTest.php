<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Middleware;

use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
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
use function str_replace;

/**
 * The account link rewrite with module prefixing turned off
 *
 * The prefixed rewrite is covered by the controller tests, which run with the default
 * `apiPrefix: true`. These read the same resources unprefixed and expect the same documents
 * with the module segment dropped from every link.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class UrlFormatTest extends Tests\Cases\Unit\DbTestCase
{

	public function setUp(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/unprefixedRoutes.neon');

		parent::setUp();
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
	#[DataProvider('unprefixedRead')]
	public function testUnprefixedRead(string $url, string $token, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			[
				'authorization' => $token,
			],
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
			static fn (string $expectation): string => str_replace(
				'/api/' . Constants::MODULE_ACCOUNTS_PREFIX . '/v1/',
				'/api/v1/',
				$expectation,
			),
		);
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
	public function testUnprefixedAccountSelfLink(): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			'/api/v1/me',
			[
				'authorization' => 'Bearer ' . self::ADMINISTRATOR_TOKEN,
			],
		);

		$response = $router->handle($request);

		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

		$document = Utils\Json::decode((string) $response->getBody(), forceArrays: true);

		self::assertIsArray($document);
		self::assertIsArray($document['data'] ?? null);
		self::assertIsArray($document['data']['links'] ?? null);
		self::assertSame('/api/v1/me', $document['data']['links']['self'] ?? null);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function unprefixedRead(): array
	{
		return [
			'me' => [
				'/api/v1/me',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.json',
			],
			'meUser' => [
				'/api/v1/me',
				'Bearer ' . self::USER_TOKEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.user.json',
			],
			'meWithIncluded' => [
				'/api/v1/me?include=emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.included.json',
			],
			'session' => [
				'/api/v1/session',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.read.json',
			],
		];
	}

}
