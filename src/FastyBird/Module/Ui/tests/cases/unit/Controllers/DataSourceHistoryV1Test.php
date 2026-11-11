<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL;
use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
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
use function http_build_query;
use function sprintf;
use const PHP_QUERY_RFC3986;

/**
 * The history of a data source: `GET /widgets/{widget}/data-sources/{id}/history` (#624)
 *
 * The container has the dummy reader, which answers with what it was asked for, so a response shows the period
 * and the number of points the controller made of the query. The clock of the container is frozen at NOW.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DataSourceHistoryV1Test extends Tests\Cases\Unit\DbTestCase
{

	private const int NOW = 1_585_742_400;

	private const int DAY = 86_400;

	// The room temperature widget, its generic data source and its chart graph display
	private const string WIDGET = '15553443-4564-454d-af04-0dfeef08aa96';

	private const string DATA_SOURCE = '764937a7-8565-472e-8e12-fe97cd55a377';

	// The TV light widget, whose generic data source has no history for the dummy reader
	private const string OTHER_WIDGET = '5626e7a1-c42c-4a31-9b5d-848e3cf0e82a';

	private const string WITHOUT_HISTORY = Tests\Fixtures\Dummy\DummyHistoryReader::WITHOUT_HISTORY;

	/**
	 * @return array<string, array{array<string, mixed>, int, int, int}> the query, and what the
	 *                                                                                   reader is asked for: the start, the end and the points
	 */
	public static function periods(): array
	{
		return [
			'the default is a day back from now' => [[], self::NOW - self::DAY, self::NOW, 500],
			'points' => [['filter' => ['points' => '1']], self::NOW - self::DAY, self::NOW, 1],
			'the most points' => [['filter' => ['points' => '2000']], self::NOW - self::DAY, self::NOW, 2_000],
			'a period' => [['filter' => ['period' => '3600']], self::NOW - 3_600, self::NOW, 500],
			'a period with a leading zero' => [['filter' => ['period' => '03600']], self::NOW - 3_600, self::NOW, 500],
			'points with a leading zero' => [['filter' => ['points' => '05']], self::NOW - self::DAY, self::NOW, 5],
			'the shortest period' => [['filter' => ['period' => '60']], self::NOW - 60, self::NOW, 500],
			'the longest period' => [['filter' => ['period' => '31622400']], self::NOW - 31_622_400, self::NOW, 500],
			'to' => [
				['filter' => ['to' => '2020-03-31T12:00:00+00:00', 'period' => '3600']],
				self::NOW - self::DAY - 3_600,
				self::NOW - self::DAY,
				500,
			],
			'from and to' => [
				['filter' => ['from' => '2020-03-31T00:00:00+00:00', 'to' => '2020-04-01T00:00:00+00:00']],
				self::NOW - self::DAY - 12 * 3_600,
				self::NOW - 12 * 3_600,
				500,
			],
			'from alone ends now' => [
				['filter' => ['from' => '2020-04-01T10:00:00+00:00']],
				self::NOW - 2 * 3_600,
				self::NOW,
				500,
			],
			'Z for UTC' => [
				['filter' => ['from' => '2020-04-01T10:00:00Z', 'to' => '2020-04-01T11:00:00Z']],
				self::NOW - 2 * 3_600,
				self::NOW - 3_600,
				500,
			],
			'an offset is the same instant' => [
				['filter' => ['from' => '2020-04-01T12:00:00+02:00', 'to' => '2020-04-01T13:00:00+02:00']],
				self::NOW - 2 * 3_600,
				self::NOW - 3_600,
				500,
			],
			'the longest range' => [
				['filter' => ['from' => '2019-04-01T12:00:00+00:00', 'to' => '2020-04-01T12:00:00+00:00']],
				self::NOW - 366 * self::DAY,
				self::NOW,
				500,
			],
		];
	}

	/**
	 * @param array<string, mixed> $query
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('periods')]
	public function testPeriodAndPointsAreWhatTheReaderIsAskedFor(
		array $query,
		int $from,
		int $to,
		int $points,
	): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		[$status, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE, $query));

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);

		$attributes = $this->attributes($document);

		self::assertSame([$from, $to], $attributes['time']);
		self::assertSame($points, $attributes['resolution']);
		self::assertSame($this->iso($from), $attributes['from']);
		self::assertSame($this->iso($to), $attributes['to']);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testResponse(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(
			new ServerRequest(
				RequestMethodInterface::METHOD_GET,
				$this->url(self::WIDGET, self::DATA_SOURCE, ['filter' => ['points' => '10']]),
				['authorization' => 'Bearer ' . self::VALID_TOKEN],
			),
		);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
		self::assertSame('application/vnd.api+json', $response->getHeaderLine('Content-Type'));
		Tests\Support\JsonAssert::assertFixtureMatch(
			__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.history.json',
			(string) $response->getBody(),
		);
	}

	/**
	 * The chart display of the widget sets the period when the query does not
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 * @throws DBAL\Exception
	 */
	public function testPeriodOfTheDisplayIsTheDefault(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$this->getDb()->executeStatement(
			'UPDATE fb_ui_module_widgets_display SET params = ? WHERE display_type = ?',
			['{"period": 7200}', 'chart-graph'],
		);

		[$status, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE));

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertSame([self::NOW - 7_200, self::NOW], $this->attributes($document)['time']);

		// The query wins
		[, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE, ['filter' => ['period' => '600']]));

		self::assertSame([self::NOW - 600, self::NOW], $this->attributes($document)['time']);
	}

	/**
	 * A period the display holds outside the bounds the API takes, put there by hand, is the default
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 * @throws DBAL\Exception
	 */
	public function testPeriodOfTheDisplayOutsideTheBoundsIsIgnored(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$this->getDb()->executeStatement(
			'UPDATE fb_ui_module_widgets_display SET params = ? WHERE display_type = ?',
			['{"period": 5}', 'chart-graph'],
		);

		[$status, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE));

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertSame([self::NOW - self::DAY, self::NOW], $this->attributes($document)['time']);
	}

	/**
	 * @return array<string, array{array<string, mixed>, string}> the query, and the parameter the
	 *                                                                           error points at
	 */
	public static function invalid(): array
	{
		return [
			'points 0' => [['filter' => ['points' => '0']], 'filter[points]'],
			'points above the limit' => [['filter' => ['points' => '2001']], 'filter[points]'],
			'points negative' => [['filter' => ['points' => '-5']], 'filter[points]'],
			'points a word' => [['filter' => ['points' => 'many']], 'filter[points]'],
			'points a fraction' => [['filter' => ['points' => '1.5']], 'filter[points]'],
			'points with a leading space' => [['filter' => ['points' => ' 5']], 'filter[points]'],
			'points with a trailing space' => [['filter' => ['points' => '5 ']], 'filter[points]'],
			'points with a trailing newline' => [['filter' => ['points' => "5\n"]], 'filter[points]'],
			'points with a plus sign' => [['filter' => ['points' => '+5']], 'filter[points]'],
			'points in hexadecimal' => [['filter' => ['points' => '0x10']], 'filter[points]'],
			'points beyond an integer' => [['filter' => ['points' => '99999999999999999999']], 'filter[points]'],
			'points empty' => [['filter' => ['points' => '']], 'filter[points]'],
			'period below the shortest' => [['filter' => ['period' => '59']], 'filter[period]'],
			'period above the longest' => [['filter' => ['period' => '31622401']], 'filter[period]'],
			'period a word' => [['filter' => ['period' => 'day']], 'filter[period]'],
			'period with a leading space' => [['filter' => ['period' => ' 3600']], 'filter[period]'],
			'period with a trailing space' => [['filter' => ['period' => '3600 ']], 'filter[period]'],
			'period with a trailing newline' => [['filter' => ['period' => "3600\n"]], 'filter[period]'],
			'period with a plus sign' => [['filter' => ['period' => '+3600']], 'filter[period]'],
			'period negative' => [['filter' => ['period' => '-3600']], 'filter[period]'],
			'period beyond an integer' => [['filter' => ['period' => '99999999999999999999']], 'filter[period]'],
			'period with from' => [
				['filter' => ['period' => '3600', 'from' => '2020-04-01T10:00:00+00:00']],
				'filter[period]',
			],
			'from a date only' => [['filter' => ['from' => '2020-04-01']], 'filter[from]'],
			'from a word' => [['filter' => ['from' => 'yesterday']], 'filter[from]'],
			'from a date that does not exist' => [['filter' => ['from' => '2020-02-31T00:00:00+00:00']], 'filter[from]'],
			'from without an offset' => [['filter' => ['from' => '2020-04-01T10:00:00']], 'filter[from]'],
			'from with single digits' => [['filter' => ['from' => '2020-4-1T10:0:0+00:00']], 'filter[from]'],
			'from with trailing text' => [['filter' => ['from' => '2020-04-01T10:00:00+00:00x']], 'filter[from]'],
			'from with a lower case z' => [['filter' => ['from' => '2020-04-01T10:00:00z']], 'filter[from]'],
			'from with a space' => [['filter' => ['from' => '2020-04-01 10:00:00+00:00']], 'filter[from]'],
			'from an epoch' => [['filter' => ['from' => '1585735200']], 'filter[from]'],
			'from a list' => [['filter' => ['from' => ['2020-04-01T10:00:00+00:00']]], 'filter[from]'],
			'to a word' => [['filter' => ['to' => 'now']], 'filter[to]'],
			'an unknown filter' => [['filter' => ['step' => '5']], 'filter[step]'],
			'filter that is not a list' => [['filter' => 'all'], 'filter'],
			'from after to' => [
				['filter' => ['from' => '2020-04-01T13:00:00+00:00', 'to' => '2020-04-01T12:00:00+00:00']],
				'filter[from]',
			],
			'from at to' => [
				['filter' => ['from' => '2020-04-01T12:00:00+00:00', 'to' => '2020-04-01T12:00:00+00:00']],
				'filter[from]',
			],
			'from a second beyond the longest range' => [
				['filter' => ['from' => '2019-04-01T11:59:59+00:00', 'to' => '2020-04-01T12:00:00+00:00']],
				'filter[from]',
			],
			'from in the future' => [['filter' => ['from' => '2020-04-01T13:00:00+00:00']], 'filter[from]'],
		];
	}

	/**
	 * @param array<string, mixed> $query
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('invalid')]
	public function testInvalidQueryIsABadRequest(array $query, string $parameter): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		[$status, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE, $query));

		self::assertSame(StatusCodeInterface::STATUS_BAD_REQUEST, $status);
		self::assertIsArray($document['errors']);
		self::assertIsArray($document['errors'][0]);
		self::assertSame('400', $document['errors'][0]['status']);
		self::assertIsArray($document['errors'][0]['source']);
		self::assertSame($parameter, $document['errors'][0]['source']['parameter']);
	}

	/**
	 * @return array<string, array{string}> the path of the data source
	 */
	public static function notFound(): array
	{
		return [
			'an unknown widget' => ['/widgets/bb369e71-ada6-4d1a-a5a8-b6ee5cd58296/data-sources/' . self::DATA_SOURCE],
			'a widget that is not a uuid' => ['/widgets/room/data-sources/' . self::DATA_SOURCE],
			'an unknown data source' => ['/widgets/' . self::WIDGET . '/data-sources/69786d15-fd0c-4d9f-9378-33287c2009af'],
			'a data source that is not a uuid' => ['/widgets/' . self::WIDGET . '/data-sources/temperature'],
			'the data source of another widget' => ['/widgets/' . self::OTHER_WIDGET . '/data-sources/' . self::DATA_SOURCE],
			'a data source with no history' => ['/widgets/' . self::OTHER_WIDGET . '/data-sources/' . self::WITHOUT_HISTORY],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('notFound')]
	public function testNotFound(string $path): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(
			new ServerRequest(
				RequestMethodInterface::METHOD_GET,
				'/api/' . Ui\DI\UiExtension::MODULE_UI_PREFIX . '/v1' . $path . '/history',
				['authorization' => 'Bearer ' . self::VALID_TOKEN],
			),
		);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());
		Tests\Support\JsonAssert::assertFixtureMatch(
			__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			(string) $response->getBody(),
		);
	}

	/**
	 * With no module that knows the property of a data source, nothing has a history
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testNotFoundWithoutAReader(): void
	{
		[$status] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE));

		self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $status);
	}

	/**
	 * @return array<string, array{string|null, int, string}>
	 */
	public static function access(): array
	{
		$dir = __DIR__ . '/../../../fixtures/Controllers/responses/generic/';

		return [
			'a missing token' => [null, StatusCodeInterface::STATUS_FORBIDDEN, $dir . 'forbidden.json'],
			'an empty token' => ['', StatusCodeInterface::STATUS_FORBIDDEN, $dir . 'forbidden.json'],
			'an invalid token' => [
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				$dir . 'unauthorized.json',
			],
			'an expired token' => [
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				$dir . 'unauthorized.json',
			],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	#[DataProvider('access')]
	public function testAccessNeedsAValidToken(string|null $token, int $statusCode, string $fixture): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$router = $this->getContainer()->getByType(Routing\Router::class);

		$response = $router->handle(
			new ServerRequest(
				RequestMethodInterface::METHOD_GET,
				$this->url(self::WIDGET, self::DATA_SOURCE),
				$token === null ? [] : ['authorization' => $token],
			),
		);

		self::assertTrue($response instanceof Http\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Support\JsonAssert::assertFixtureMatch($fixture, (string) $response->getBody());
	}

	/**
	 * Any signed-in user may read it, as any may read the data source itself
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testAnyUserMayRead(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		[$status] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE), 'Bearer ' . self::VALID_TOKEN_USER);

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
	}

	/**
	 * It is a read: the other methods are not routes
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testOnlyGetIsRouted(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		$router = $this->getContainer()->getByType(Routing\Router::class);

		foreach ([
			RequestMethodInterface::METHOD_POST,
			RequestMethodInterface::METHOD_PATCH,
			RequestMethodInterface::METHOD_DELETE,
		] as $method) {
			$response = $router->handle(
				new ServerRequest(
					$method,
					$this->url(self::WIDGET, self::DATA_SOURCE),
					['authorization' => 'Bearer ' . self::VALID_TOKEN],
				),
			);

			self::assertTrue($response instanceof Http\Response);
			self::assertContains($response->getStatusCode(), [
				StatusCodeInterface::STATUS_NOT_FOUND,
				StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED,
			], $method);
		}
	}

	/**
	 * The route has its own name, and a link built from it is the one that answers
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws CoreExceptions\Runtime
	 * @throws UiExceptions\InvalidArgument
	 */
	public function testRouteName(): void
	{
		$router = $this->getContainer()->getByType(Routing\Router::class);

		self::assertSame(
			'/api/' . Ui\DI\UiExtension::MODULE_UI_PREFIX . '/v1/widgets/' . self::WIDGET . '/data-sources/' . self::DATA_SOURCE . '/history',
			$router->urlFor(
				Ui\Router\ApiRoutes::ROUTE_NAME_WIDGET_DATA_SOURCE_HISTORY,
				[
					Ui\Router\ApiRoutes::URL_WIDGET_ID => self::WIDGET,
					Ui\Router\ApiRoutes::URL_ITEM_ID => self::DATA_SOURCE,
				],
			),
		);
	}

	/**
	 * The data source of the response is the entity of the relationship, whatever its type
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 */
	public function testIncludesTheDataSource(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/history.neon');

		[$status, $document] = $this->get($this->url(self::WIDGET, self::DATA_SOURCE, ['include' => 'data_source']));

		self::assertSame(StatusCodeInterface::STATUS_OK, $status);
		self::assertIsArray($document['included']);
		self::assertIsArray($document['included'][0]);
		self::assertSame(self::DATA_SOURCE, $document['included'][0]['id']);
		self::assertSame('com.fastybird.ui-module/data-source/generic', $document['included'][0]['type']);
	}

	/**
	 * @param array<string, mixed> $query
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
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

	private function iso(int $time): string
	{
		return (new DateTimeImmutable())->setTimestamp($time)->setTimezone(new DateTimeZone('UTC'))->format(
			DateTimeInterface::ATOM,
		);
	}

}
