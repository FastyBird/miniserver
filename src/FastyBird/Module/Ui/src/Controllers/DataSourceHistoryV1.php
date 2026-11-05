<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Controllers;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\Core\Api\Exceptions as ApiExceptions;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Router;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Clock\ClockInterface;
use Psr\Http\Message;
use Ramsey\Uuid;
use ValueError;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function filter_var;
use function is_array;
use function is_string;
use function preg_match;
use function str_ends_with;
use function strval;
use function substr;
use const FILTER_VALIDATE_INT;

/**
 * API data source history controller
 *
 * The history of the property a data source shows, for a chart. The period comes from the query:
 * `filter[from]` and `filter[to]`, ISO 8601 dates, or `filter[period]`, a number of seconds back from `to`,
 * which is now when it is not given. When neither `from` nor `period` is, the period is the one of the
 * widget's chart display, or a day. `filter[points]` is how many points the series may have.
 *
 * @Secured\User(loggedIn)
 */
final class DataSourceHistoryV1 extends BaseV1
{

	public const int DEFAULT_POINTS = 500;

	public const int MAX_POINTS = 2_000;

	private const array FILTERS = ['from', 'to', 'period', 'points'];

	public function __construct(
		private readonly Models\Configuration\Widgets\Repository $widgetsConfigurationRepository,
		private readonly Models\Configuration\Widgets\DataSources\Repository $dataSourcesConfigurationRepository,
		private readonly Models\Configuration\Widgets\Displays\Repository $displaysConfigurationRepository,
		private readonly ClockInterface $clock,
		private readonly Models\History\Reader|null $reader = null,
	)
	{
	}

	/**
	 * @throws Exception
	 * @throws ApiExceptions\JsonApi
	 * @throws UiExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws ValueError
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$widget = $this->findWidget($this->attribute($request, Router\ApiRoutes::URL_WIDGET_ID));

		$dataSource = $this->findDataSource($this->attribute($request, Router\ApiRoutes::URL_ITEM_ID), $widget);

		$filter = $this->filter($request);

		$points = $this->points($filter);
		$to = $this->date($filter, 'to') ?? $this->clock->now();
		$from = $this->from($filter, $to, $widget);

		if (
			$from >= $to
			|| $to->getTimestamp() - $from->getTimestamp() > Entities\Widgets\Displays\ChartGraph::MAX_PERIOD
		) {
			throw new ApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_BAD_REQUEST,
				strval($this->translator->translate('//ui-module.history.messages.invalidRange.heading')),
				strval($this->translator->translate(
					'//ui-module.history.messages.invalidRange.message',
					['days' => Entities\Widgets\Displays\ChartGraph::MAX_PERIOD / 86_400],
				)),
				['parameter' => 'filter[from]'],
			);
		}

		$series = $this->reader?->read($dataSource, $from, $to, $points);

		if ($series === null) {
			throw $this->notFound();
		}

		return $this->builder->build(
			$request,
			$response,
			$series,
			null,
			fn (string $link): bool => $this->routesValidator->validate($link),
		);
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 * @throws UiExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function findWidget(string $id): Documents\Widgets\Widget
	{
		$widget = Uuid\Uuid::isValid($id)
			? $this->widgetsConfigurationRepository->find(Uuid\Uuid::fromString($id))
			: null;

		if ($widget === null) {
			throw $this->notFound();
		}

		return $widget;
	}

	/**
	 * @throws ApiExceptions\JsonApiError
	 * @throws UiExceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function findDataSource(
		string $id,
		Documents\Widgets\Widget $widget,
	): Documents\Widgets\DataSources\DataSource
	{
		$dataSource = null;

		if (Uuid\Uuid::isValid($id)) {
			$findQuery = new Queries\Configuration\FindWidgetDataSources();
			$findQuery->forWidget($widget);
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$dataSource = $this->dataSourcesConfigurationRepository->findOneBy($findQuery);
		}

		if ($dataSource === null) {
			throw $this->notFound();
		}

		return $dataSource;
	}

	/**
	 * @return array<string, string>
	 *
	 * @throws ApiExceptions\JsonApiError
	 */
	private function filter(Message\ServerRequestInterface $request): array
	{
		$query = $request->getQueryParams();

		if (!array_key_exists('filter', $query)) {
			return [];
		}

		$filter = $query['filter'];

		if (!is_array($filter)) {
			throw $this->invalidParameter('filter');
		}

		foreach (array_diff(array_keys($filter), self::FILTERS) as $unknown) {
			throw $this->invalidParameter('filter[' . $unknown . ']');
		}

		$values = [];

		foreach ($filter as $name => $value) {
			if (!is_string($value)) {
				throw $this->invalidParameter('filter[' . $name . ']');
			}

			$values[(string) $name] = $value;
		}

		return $values;
	}

	/**
	 * @param array<string, string> $filter
	 *
	 * @throws ApiExceptions\JsonApiError
	 */
	private function points(array $filter): int
	{
		if (!array_key_exists('points', $filter)) {
			return self::DEFAULT_POINTS;
		}

		$points = filter_var($filter['points'], FILTER_VALIDATE_INT);

		if ($points === false || $points < 1 || $points > self::MAX_POINTS) {
			throw $this->invalidParameter('filter[points]');
		}

		return $points;
	}

	/**
	 * @param array<string, string> $filter
	 *
	 * @throws ApiExceptions\JsonApiError
	 * @throws UiExceptions\InvalidState
	 * @throws ValueError
	 */
	private function from(array $filter, DateTimeImmutable $to, Documents\Widgets\Widget $widget): DateTimeImmutable
	{
		if (array_key_exists('from', $filter)) {
			if (array_key_exists('period', $filter)) {
				throw $this->invalidParameter('filter[period]');
			}

			return $this->date($filter, 'from') ?? $to;
		}

		if (array_key_exists('period', $filter)) {
			$period = filter_var($filter['period'], FILTER_VALIDATE_INT);

			if (
				$period === false
				|| $period < Entities\Widgets\Displays\ChartGraph::MIN_PERIOD
				|| $period > Entities\Widgets\Displays\ChartGraph::MAX_PERIOD
			) {
				throw $this->invalidParameter('filter[period]');
			}
		} else {
			$period = $this->displayPeriod($widget);
		}

		return $to->setTimestamp($to->getTimestamp() - $period);
	}

	/**
	 * The period of the chart display of the widget, and the default period when it has none
	 *
	 * @throws UiExceptions\InvalidState
	 */
	private function displayPeriod(Documents\Widgets\Widget $widget): int
	{
		$findQuery = new Queries\Configuration\FindWidgetDisplays();
		$findQuery->forWidget($widget);

		$display = $this->displaysConfigurationRepository->findOneBy($findQuery);

		return $display instanceof Documents\Widgets\Displays\ChartGraph
			? $display->getPeriod()
			: Entities\Widgets\Displays\ChartGraph::DEFAULT_PERIOD;
	}

	/**
	 * @param array<string, string> $filter
	 *
	 * @throws ApiExceptions\JsonApiError
	 * @throws ValueError
	 */
	private function date(array $filter, string $name): DateTimeImmutable|null
	{
		if (!array_key_exists($name, $filter)) {
			return null;
		}

		$value = $filter[$name];

		// ATOM's `P` takes an offset, and an ISO 8601 date may end in `Z` instead
		if (str_ends_with($value, 'Z')) {
			$value = substr($value, 0, -1) . '+00:00';
		}

		$date = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $value) === 1
			? DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value)
			: false;

		$errors = DateTimeImmutable::getLastErrors();

		// A date that does not exist, 2020-02-31, is rolled over by the parser with a warning
		if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
			throw $this->invalidParameter('filter[' . $name . ']');
		}

		return $date;
	}

	private function attribute(Message\ServerRequestInterface $request, string $name): string
	{
		$value = $request->getAttribute($name);

		return is_string($value) ? $value : '';
	}

	private function invalidParameter(string $parameter): ApiExceptions\JsonApiError
	{
		return new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_BAD_REQUEST,
			strval($this->translator->translate('//ui-module.history.messages.invalidParameter.heading')),
			strval($this->translator->translate(
				'//ui-module.history.messages.invalidParameter.message',
				['parameter' => $parameter],
			)),
			['parameter' => $parameter],
		);
	}

	private function notFound(): ApiExceptions\JsonApiError
	{
		return new ApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_NOT_FOUND,
			strval($this->translator->translate('//ui-module.base.messages.notFound.heading')),
			strval($this->translator->translate('//ui-module.base.messages.notFound.message')),
		);
	}

}
