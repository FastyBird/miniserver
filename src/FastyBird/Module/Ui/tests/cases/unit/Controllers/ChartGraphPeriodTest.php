<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Controllers;

use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Tests;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use React\Http\Message\ServerRequest;
use Throwable;

/**
 * The `period` setting of the chart graph display: the seconds of history a chart shows when it opens (#624)
 *
 * It lives in the display's params, so there is no column and no migration. It is a whole number from a minute to
 * 366 days, and a day when the display has none.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ChartGraphPeriodTest extends Tests\Cases\Unit\DbTestCase
{

	private const string URL = '/api/' . Ui\DI\UiExtension::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/display';

	/**
	 * @throws Throwable
	 */
	public function testNewDisplayHasADay(): void
	{
		[$status, $document] = $this->request(RequestMethodInterface::METHOD_GET);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertSame(86_400, $this->attributes($document)['period']);
	}

	/**
	 * @return array<string, array{int}>
	 */
	public static function periods(): array
	{
		return [
			'a minute' => [60],
			'an hour' => [3_600],
			'a week' => [604_800],
			'366 days' => [31_622_400],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('periods')]
	public function testPeriodIsStoredAndRead(int $period): void
	{
		[$status, $document] = $this->request(RequestMethodInterface::METHOD_PATCH, ['period' => $period]);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertSame($period, $this->attributes($document)['period']);

		// Out of the database, not the identity map
		$this->getEntityManager()->clear();

		[, $document] = $this->request(RequestMethodInterface::METHOD_GET);

		self::assertSame($period, $this->attributes($document)['period']);
		self::assertSame($period, $this->params($document)['period']);
	}

	/**
	 * @throws Throwable
	 */
	public function testNullPeriodIsTheDefault(): void
	{
		$this->request(RequestMethodInterface::METHOD_PATCH, ['period' => 3_600]);

		[$status, $document] = $this->request(RequestMethodInterface::METHOD_PATCH, ['period' => null]);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertSame(86_400, $this->attributes($document)['period']);
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function invalid(): array
	{
		return [
			'below a minute' => [59],
			'zero' => [0],
			'negative' => [-3_600],
			'above 366 days' => [31_622_401],
			'a fraction' => [3_600.5],
			'a number in a string' => ['3600'],
			'a word' => ['week'],
			'a bool' => [true],
			'a list' => [[3_600]],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('invalid')]
	public function testInvalidPeriodIsRefused(mixed $period): void
	{
		[$status, $document] = $this->request(RequestMethodInterface::METHOD_PATCH, ['period' => $period]);

		self::assertSame(StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY, $status);
		self::assertIsArray($document['errors']);
		self::assertIsArray($document['errors'][0]);
		self::assertIsArray($document['errors'][0]['source']);
		self::assertSame('/data/attributes/period', $document['errors'][0]['source']['pointer']);

		// And nothing was stored
		[, $document] = $this->request(RequestMethodInterface::METHOD_GET);

		self::assertSame(86_400, $this->attributes($document)['period']);
	}

	/**
	 * A period the params hold outside the bounds, put there by hand, is the default
	 *
	 * @throws Throwable
	 */
	#[DataProvider('outsideTheBounds')]
	public function testPeriodOutsideTheBoundsInTheParamsIsTheDefault(string $params): void
	{
		$this->getDb()->executeStatement(
			'UPDATE fb_ui_module_widgets_display SET params = ? WHERE display_type = ?',
			[$params, 'chart-graph'],
		);

		[$status, $document] = $this->request(RequestMethodInterface::METHOD_GET);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertSame(86_400, $this->attributes($document)['period']);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function outsideTheBounds(): array
	{
		return [
			'too short' => ['{"period": 5}'],
			'too long' => ['{"period": 99999999}'],
			'a word' => ['{"period": "week"}'],
			'null' => ['{"period": null}'],
		];
	}

	/**
	 * The other displays have no period: they do not take it, and do not report it
	 *
	 * @throws Throwable
	 */
	public function testOtherDisplaysHaveNone(): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(
			new ServerRequest(
				RequestMethodInterface::METHOD_GET,
				'/api/' . Ui\DI\UiExtension::MODULE_UI_PREFIX . '/v1/widgets/1d600901-54e7-43ee-8f5d-a9e22663ddd7/display',
				['authorization' => 'Bearer ' . self::VALID_TOKEN],
			),
		);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
		self::assertStringNotContainsString('"period"', (string) $response->getBody());
	}

	/**
	 * @param array<string, mixed>|null $attributes
	 *
	 * @return array{int, array<mixed>}
	 *
	 * @throws Throwable
	 */
	private function request(string $method, array|null $attributes = null): array
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$body = $attributes === null ? '' : Utils\Json::encode([
			'data' => [
				'id' => '467e6d4d-3545-481b-b613-53be7e9aa641',
				'type' => 'com.fastybird.ui-module/display/chart-graph',
				'attributes' => $attributes,
			],
		]);

		$response = $router->handle(
			new ServerRequest($method, self::URL, ['authorization' => 'Bearer ' . self::VALID_TOKEN], $body),
		);

		self::assertTrue($response instanceof Http\Response);

		$document = Utils\Json::decode((string) $response->getBody(), forceArrays: true);

		self::assertIsArray($document);

		return [$response->getStatusCode(), $document];
	}

	/**
	 * @param array<mixed> $document
	 *
	 * @return array<string, mixed>
	 */
	private function attributes(array $document): array
	{
		self::assertIsArray($document['data']);
		self::assertIsArray($document['data']['attributes']);

		$attributes = [];

		foreach ($document['data']['attributes'] as $name => $value) {
			$attributes[(string) $name] = $value;
		}

		return $attributes;
	}

	/**
	 * @param array<mixed> $document
	 *
	 * @return array<string, mixed>
	 */
	private function params(array $document): array
	{
		$attributes = $this->attributes($document);

		self::assertIsArray($attributes['params']);

		$params = [];

		foreach ($attributes['params'] as $name => $value) {
			$params[(string) $name] = $value;
		}

		return $params;
	}

}
