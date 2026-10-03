<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Core\Constants;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Tests;
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
final class WidgetsV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('widgetsRead')]
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(RequestMethodInterface::METHOD_GET, $url, $headers);

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
	public static function widgetsRead(): array
	{
		return [
			'readAll' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.index.json',
			],
			'readAllPaging' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.index.paging.json',
			],
			'readOne' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.read.json',
			],
			'readOneWithInclude' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96?include=display,data-sources',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.read.include.json',
			],
			'readOneUnknown' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/69786d15-fd0c-4d9f-9378-33287c2009af',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsDisplay' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/relationships/display',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.readRelationships.display.json',
			],
			'readRelationshipsDataSources' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/relationships/data-sources',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.readRelationships.dataSources.json',
			],
			'readRelationshipsGroups' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/relationships/groups',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.readRelationships.groups.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('widgetsCreate')]
	public function testCreate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

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

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function widgetsCreate(): array
	{
		return [
			'create' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.create.json',
			],
			'missingRequired' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.missing.required.json',
			],
			// #[Crud(required: true)] on Widget::$display, enforced by the hydrator's CrudReader
			'missingDisplay' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.missing.display.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.create.missing.display.json',
			],
			'invalidType' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'invalidDisplay' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.invalidDisplay.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.create.invalidDisplay.json',
			],
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('widgetsUpdate')]
	public function testUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

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
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function widgetsUpdate(): array
	{
		return [
			'update' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96?include=display,data-sources',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.update.json',
			],
			'invalidType' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96?include=display,data-sources',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.update.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96?include=display,data-sources',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.update.idMismatch.json'),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'notFound' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/55553443-4564-454d-af04-0dfeef08aa96?include=display,data-sources',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.update.notFound.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

	/**
	 * The display values arrive on the included display resource. Widget::buildDisplay() hydrates
	 * them with the widget hydrator's own attribute map, so this test guards those map entries.
	 *
	 * @param array<string, bool|float|int|string|null> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('widgetsDisplayValues')]
	public function testDisplayValues(
		string $method,
		string $url,
		string $body,
		int $statusCode,
		string $widgetId,
		array $expected,
	): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$request = new ServerRequest(
			$method,
			$url,
			[
				'authorization' => 'Bearer ' . self::VALID_TOKEN,
			],
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode(), (string) $response->getBody());

		// Read the display back from the database, not from the identity map
		$this->getEntityManager()->clear();

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/' . $widgetId . '/display',
			[
				'authorization' => 'Bearer ' . self::VALID_TOKEN,
			],
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

		$document = Utils\Json::decode((string) $response->getBody(), forceArrays: true);
		self::assertIsArray($document);
		self::assertIsArray($document['data']);
		self::assertIsArray($document['data']['attributes']);

		foreach ($expected as $attribute => $value) {
			self::assertArrayHasKey($attribute, $document['data']['attributes']);
			self::assertSame($value, $document['data']['attributes'][$attribute], $attribute);
		}
	}

	/**
	 * @return array<string, array<bool|string|int|array<string, bool|float|int|string|null>>>
	 */
	public static function widgetsDisplayValues(): array
	{
		return [
			'createChartGraph' => [
				RequestMethodInterface::METHOD_POST,
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.chartGraph.json'),
				StatusCodeInterface::STATUS_CREATED,
				'6d1b4d1c-5a53-4b8e-9a0e-2f7a3c6e1b01',
				[
					'minimum_value' => 5,
					'maximum_value' => 40,
					'step_value' => 0.5,
					'precision' => 1,
					'enable_min_max' => true,
				],
			],
			// Slider takes minimum, maximum and step as required constructor arguments
			'createSlider' => [
				RequestMethodInterface::METHOD_POST,
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.slider.json'),
				StatusCodeInterface::STATUS_CREATED,
				'6d1b4d1c-5a53-4b8e-9a0e-2f7a3c6e1b03',
				[
					'minimum_value' => 10,
					'maximum_value' => 30,
					'step_value' => 2.5,
					'precision' => 1,
				],
			],
			'createButton' => [
				RequestMethodInterface::METHOD_POST,
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.button.json'),
				StatusCodeInterface::STATUS_CREATED,
				'6d1b4d1c-5a53-4b8e-9a0e-2f7a3c6e1b05',
				[
					'icon' => 'thermometer',
				],
			],
			'updateChartGraph' => [
				RequestMethodInterface::METHOD_PATCH,
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.update.display.json'),
				StatusCodeInterface::STATUS_OK,
				'15553443-4564-454d-af04-0dfeef08aa96',
				[
					'minimum_value' => 10,
					'maximum_value' => 30,
					'step_value' => 0.5,
					'precision' => 2,
					'enable_min_max' => true,
				],
			],
		];
	}

	/**
	 * The included data sources are hydrated by Widget::hydrateDataSourcesRelationship(), not by
	 * the data source hydrator's own hydrate(). This test guards that their `params` survive it.
	 * Data source schemas expose no attributes, so the entity is read back from the database.
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testCreateKeepsIncludedDataSourceParams(): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets',
			[
				'authorization' => 'Bearer ' . self::VALID_TOKEN,
			],
			file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/widgets.create.dataSourceParams.json'),
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(
			StatusCodeInterface::STATUS_CREATED,
			$response->getStatusCode(),
			(string) $response->getBody(),
		);

		// Read the data source back from the database, not from the identity map
		$this->getEntityManager()->clear();

		$dataSourcesRepository = $this->getContainer()->getByType(
			Models\Entities\Widgets\DataSources\Repository::class,
		);

		$dataSource = $dataSourcesRepository->find(Uuid\Uuid::fromString('6d1b4d1c-5a53-4b8e-9a0e-2f7a3c6e1b09'));

		self::assertInstanceOf(Entities\Widgets\DataSources\Generic::class, $dataSource);
		self::assertSame('6d1b4d1c-5a53-4b8e-9a0e-2f7a3c6e1b07', $dataSource->getWidget()->getId()->toString());
		self::assertSame(
			[
				'label' => 'Room temperature',
				'position' => 2,
			],
			(array) $dataSource->getParams(),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('widgetsDelete')]
	public function testDelete(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(Routing\IRouter::class);

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
	public static function widgetsDelete(): array
	{
		return [
			'delete' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/widgets.delete.json',
			],
			'deleteUnknown' => [
				'/api/' . Constants::MODULE_UI_PREFIX . '/v1/widgets/11553443-4564-454d-af04-0dfeef08aa96',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

}
