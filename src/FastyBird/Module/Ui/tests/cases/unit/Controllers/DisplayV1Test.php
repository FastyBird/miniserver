<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants\Metadata;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Http as SlimRouterHttp;
use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Module\Ui\Tests;
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
final class DisplayV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('displayRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouterRouting\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(RequestMethodInterface::METHOD_GET, $url, $headers);

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
	public static function displayRead(): array
	{
		return [
			'readOne' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/display',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/display.read.json',
			],
			'readOneUnknownWidget' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/11553443-4564-454d-af04-0dfeef08aa96/display',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsWidget' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/display/relationships/widget',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/display.readRelationships.widget.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/display/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
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
	#[DataProvider('displayUpdate')]
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
	 * @return array<string, array<bool|int|string|null>>
	 */
	public static function displayUpdate(): array
	{
		return [
			'update' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/display',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/display.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/display.update.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/display',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/display.update.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'widgetNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/11553443-4564-454d-af04-0dfeef08aa96/display',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/display.update.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

}
