<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets\DataSources;

use DateTimeInterface;
use DateTimeZone;
use FastyBird\Core\Api\Schemas;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Router;
use Neomerx\JsonApi;
use Override;
use Ramsey\Uuid;

/**
 * Data source history schema
 *
 * @template T of Models\History\Series
 * @extends  Schemas\JsonApiSchema<T>
 */
final class History extends Schemas\JsonApiSchema
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/data-source/history';

	/**
	 * Define relationships names
	 */
	public const string RELATIONSHIPS_DATA_SOURCE = 'data_source';

	public function __construct(
		private readonly Routing\Router $router,
		private readonly Models\Entities\Widgets\DataSources\Repository $dataSourcesRepository,
	)
	{
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Models\History\Series::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$utc = new DateTimeZone('UTC');

		return [
			'from' => $resource->from->setTimezone($utc)->format(DateTimeInterface::ATOM),
			'to' => $resource->to->setTimezone($utc)->format(DateTimeInterface::ATOM),
			'resolution' => $resource->resolution,
			'tier' => $resource->tier->value,
			'unit' => $resource->unit,
			'data_type' => $resource->dataType,
			'time' => $resource->time,
			'min' => $resource->min,
			'max' => $resource->max,
			'avg' => $resource->avg,
			'last' => $resource->last,
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function getSelfLink(
		$resource,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Router\ApiRoutes::ROUTE_NAME_WIDGET_DATA_SOURCE_HISTORY,
				[
					Router\ApiRoutes::URL_ITEM_ID => $resource->id,
					Router\ApiRoutes::URL_WIDGET_ID => $this->findDataSource(
						$resource,
					)->getWidget()->getId()->toString(),
				],
			),
			false,
		);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_DATA_SOURCE => [
				self::RELATIONSHIP_DATA => $this->findDataSource($resource),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	#[Override]
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_DATA_SOURCE) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_WIDGET_DATA_SOURCE,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->id,
						Router\ApiRoutes::URL_WIDGET_ID => $this->findDataSource(
							$resource,
						)->getWidget()->getId()->toString(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	private function findDataSource(Models\History\Series $resource): Entities\Widgets\DataSources\DataSource
	{
		$dataSource = $this->dataSourcesRepository->find(Uuid\Uuid::fromString($resource->id));

		if ($dataSource === null) {
			throw new Exceptions\InvalidState('Data source of the history could not be found');
		}

		return $dataSource;
	}

}
