<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\Controllers;

use Doctrine\DBAL;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use React\Http\Message\ServerRequest;
use Throwable;
use function http_build_query;
use function sprintf;
use const PHP_QUERY_RFC3986;

/**
 * The history endpoint of the Ui module, with the bridge's reader and the Devices module's history behind it (#624)
 *
 * The whole way: a request, the data source, its property, the samples in the tables, and the series that comes
 * back as {JSON:API}. The container's clock is frozen at NOW.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DataSourceHistoryV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const int NOW = 1_585_742_400;

	// The room temperature widget, with its chart graph display, and its data source on the temperature property
	private const string WIDGET = '15553443-4564-454d-af04-0dfeef08aa96';

	private const string DATA_SOURCE = '764937a7-8565-472e-8e12-fe97cd55a377';

	private const string TEMPERATURE = '28bc0d38-2f7c-4a71-aa74-27b102f8df4c';

	// The ambient light widget, with data sources the fixtures add
	private const string OTHER_WIDGET = '1d600901-54e7-43ee-8f5d-a9e22663ddd7';

	private const string MAPPED_DATA_SOURCE = 'c1d2e3f4-0516-4718-a9b0-c1d2e3f40516';

	#[Override]
	public function setUp(): void
	{
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/history.sources.sql');
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/connector.dynamic.property.sql');

		parent::setUp();
	}

	/**
	 * Points of 300 seconds are the 5-minute tier's, and the series is made of its rollups
	 *
	 * @throws Throwable
	 */
	public function testSeriesFromRollups(): void
	{
		$this->sample(self::TEMPERATURE, self::NOW - 7_200, 20.0);

		// The compactor has not built a rollup yet: nothing to show from this tier
		$query = ['filter' => ['period' => '7200', 'points' => '24']];

		[$status, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE, $query));

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);

		$data = $this->data($document);

		self::assertSame('com.fastybird.ui-module/data-source/history', $data['type']);
		self::assertSame(self::DATA_SOURCE, $data['id']);

		$attributes = $this->arrayOf($data['attributes']);

		self::assertSame('2020-04-01T10:00:00+00:00', $attributes['from']);
		self::assertSame('2020-04-01T12:00:00+00:00', $attributes['to']);
		self::assertSame(300, $attributes['resolution']);
		self::assertSame('five_minutes', $attributes['tier']);
		self::assertSame('°C', $attributes['unit']);
		self::assertSame('float', $attributes['data_type']);
		self::assertSame([], $attributes['time']);

		$this->getDb()->executeStatement(
			sprintf(
				'INSERT INTO %s (property_id, resolution, bucket_start, value_min, value_max, value_avg, value_last, samples)'
				. ' VALUES (?, 300, ?, 18, 26, 22, 24, 3)',
				DevicesEntities\History\Rollup::TABLE,
			),
			[Uuid\Uuid::fromString(self::TEMPERATURE)->getBytes(), self::NOW - 600],
			[DBAL\ParameterType::BINARY, DBAL\ParameterType::INTEGER],
		);

		[, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE, $query));

		$attributes = $this->arrayOf($this->data($document)['attributes']);

		self::assertSame([self::NOW - 600], $attributes['time']);
		self::assertEquals([18.0], $attributes['min']);
		self::assertEquals([26.0], $attributes['max']);
		self::assertEquals([22.0], $attributes['avg']);
		self::assertEquals([24.0], $attributes['last']);
	}

	/**
	 * Fine points are read from the samples, with the value that held before the period carried in, the gap
	 * kept as a gap, and every column as long as the time
	 *
	 * @throws Throwable
	 */
	public function testSeriesFromSamples(): void
	{
		$this->sample(self::TEMPERATURE, self::NOW - 7_200, 20.0);
		$this->sample(self::TEMPERATURE, self::NOW - 1_800, 24.0);
		$this->sample(self::TEMPERATURE, self::NOW - 900, null);

		// 600 seconds in 6 points: 100 seconds a point, from the samples
		[$status, $document] = $this->get(
			$this->url(self::WIDGET, self::DATA_SOURCE, ['filter' => ['period' => '600', 'points' => '6']]),
		);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);

		$attributes = $this->arrayOf($this->data($document)['attributes']);

		self::assertSame('samples', $attributes['tier']);
		self::assertSame(100, $attributes['resolution']);
		self::assertSame(
			[self::NOW - 600, self::NOW - 500, self::NOW - 400, self::NOW - 300, self::NOW - 200, self::NOW - 100],
			$attributes['time'],
		);
		// The last sample before the period is a gap, so the whole period is
		self::assertSame([null, null, null, null, null, null], $attributes['avg']);
		self::assertSame($attributes['avg'], $attributes['min']);
		self::assertSame($attributes['avg'], $attributes['max']);
		self::assertSame($attributes['avg'], $attributes['last']);
	}

	/**
	 * @throws Throwable
	 */
	public function testSeriesWithValues(): void
	{
		$this->sample(self::TEMPERATURE, self::NOW - 7_200, 20.0);
		$this->sample(self::TEMPERATURE, self::NOW - 250, 24.0);

		[$status, $document] = $this->get(
			$this->url(self::WIDGET, self::DATA_SOURCE, ['filter' => ['period' => '600', 'points' => '6']]),
		);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);

		$attributes = $this->arrayOf($this->data($document)['attributes']);

		// 20 carried in until 250 seconds before the end, then 24: the fourth point holds both for 50 seconds
		self::assertEquals([20.0, 20.0, 20.0, 22.0, 24.0, 24.0], $attributes['avg']);
		self::assertEquals([20.0, 20.0, 20.0, 20.0, 24.0, 24.0], $attributes['min']);
		self::assertEquals([20.0, 20.0, 20.0, 24.0, 24.0, 24.0], $attributes['max']);
		self::assertEquals([20.0, 20.0, 20.0, 24.0, 24.0, 24.0], $attributes['last']);
	}

	/**
	 * The relationship names the data source with the bridge's own type
	 *
	 * @throws Throwable
	 */
	public function testRelationshipToTheDataSource(): void
	{
		[$status, $document] = $this->get(
			$this->url(self::WIDGET, self::DATA_SOURCE, ['include' => 'data_source']),
		);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);

		$data = $this->data($document);
		$relationships = $this->arrayOf($data['relationships']);
		$relationship = $this->arrayOf($relationships['data_source']);

		self::assertSame(
			[
				'type' => 'com.fastybird.devices-module-ui-module-bridge/data-source/channel-property',
				'id' => self::DATA_SOURCE,
			],
			$relationship['data'],
		);

		self::assertIsArray($document['included']);
		self::assertIsArray($document['included'][0]);
		self::assertSame(self::DATA_SOURCE, $document['included'][0]['id']);
	}

	/**
	 * A data source bound to a mapped property shows its parent's series, with the parent's unit
	 *
	 * @throws Throwable
	 */
	public function testMappedPropertyShowsTheParentsSeries(): void
	{
		$this->sample(self::TEMPERATURE, self::NOW - 7_200, 20.0);

		[$status, $document] = $this->get(
			$this->url(
				self::OTHER_WIDGET,
				self::MAPPED_DATA_SOURCE,
				['filter' => ['period' => '600', 'points' => '6']],
			),
		);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);

		$attributes = $this->arrayOf($this->data($document)['attributes']);

		self::assertSame('°C', $attributes['unit']);
		self::assertEquals([20.0, 20.0, 20.0, 20.0, 20.0, 20.0], $attributes['avg']);
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function withoutHistory(): array
	{
		return [
			'a variable property' => [self::OTHER_WIDGET, 'd1e2f304-1526-4738-b9c0-d1e2f3041526'],
			'a generic data source' => [self::OTHER_WIDGET, '0a1b2c3d-4e5f-4a6b-8c7d-9e0f1a2b3c4d'],
			'the data source of another widget' => [self::WIDGET, self::MAPPED_DATA_SOURCE],
			'an unknown data source' => [self::WIDGET, '69786d15-fd0c-4d9f-9378-33287c2009af'],
		];
	}

	/**
	 * @throws Throwable
	 */
	#[DataProvider('withoutHistory')]
	public function testNotFound(string $widget, string $dataSource): void
	{
		[$status, $document] = $this->get($this->url($widget, $dataSource));

		self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $status);
		self::assertIsArray($document['errors']);
		self::assertIsArray($document['errors'][0]);
		self::assertSame('404', $document['errors'][0]['status']);
	}

	/**
	 * The signed-in user is the only thing the route asks
	 *
	 * @throws Throwable
	 */
	public function testNeedsAValidToken(): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(
			new ServerRequest(RequestMethodInterface::METHOD_GET, $this->url(self::WIDGET, self::DATA_SOURCE)),
		);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_FORBIDDEN, $response->getStatusCode());

		[$status] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE), 'Bearer ' . self::VALID_TOKEN_USER);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
	}

	/**
	 * @param array<string, string|array<string>> $query
	 */
	private function url(string $widget, string $dataSource, array $query = []): string
	{
		return sprintf(
			'/api/%s/v1/widgets/%s/data-sources/%s/history%s',
			Ui\DI\UiExtension::MODULE_UI_PREFIX,
			$widget,
			$dataSource,
			$query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986),
		);
	}

	/**
	 * @return array{int, array<mixed>}
	 *
	 * @throws Throwable
	 */
	private function get(string $url, string $token = 'Bearer ' . self::VALID_TOKEN): array
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(
			new ServerRequest(RequestMethodInterface::METHOD_GET, $url, ['authorization' => $token]),
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
	private function data(array $document): array
	{
		return $this->arrayOf($document['data']);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function arrayOf(mixed $value): array
	{
		self::assertIsArray($value);

		$result = [];

		foreach ($value as $key => $item) {
			$result[(string) $key] = $item;
		}

		return $result;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Throwable
	 */
	private function sample(string $property, int $time, float|null $value): void
	{
		$this->getDb()->executeStatement(
			sprintf(
				'INSERT INTO %s (property_id, sampled_at, value) VALUES (?, ?, ?)',
				DevicesEntities\History\Sample::TABLE,
			),
			[Uuid\Uuid::fromString($property)->getBytes(), $time, $value],
			[
				DBAL\ParameterType::BINARY,
				DBAL\ParameterType::INTEGER,
				$value === null ? DBAL\ParameterType::NULL : DBAL\ParameterType::STRING,
			],
		);
	}

}
