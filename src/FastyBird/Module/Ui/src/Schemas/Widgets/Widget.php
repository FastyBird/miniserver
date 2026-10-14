<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets;

use FastyBird\Core\Api\Schemas;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Router;
use Neomerx\JsonApi;
use Override;
use function count;

/**
 * Widget entity schema
 *
 * @template  T of Entities\Widgets\Widget
 * @extends   Schemas\JsonApiSchema<T>
 */
abstract class Widget extends Schemas\JsonApiSchema
{

	/**
	 * Define relationships names
	 */
	public const string RELATIONSHIPS_DISPLAY = 'display';

	public const string RELATIONSHIPS_TABS = 'tabs';

	public const string RELATIONSHIPS_GROUPS = 'groups';

	public const string RELATIONSHIPS_DATA_SOURCES = 'data-sources';

	public function __construct(protected readonly Routing\Router $router)
	{
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
		return [
			'identifier' => $resource->getIdentifier(),
			'name' => $resource->getName(),
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Router\ApiRoutes::ROUTE_NAME_WIDGET,
				[
					Router\ApiRoutes::URL_ITEM_ID => $resource->getId()->toString(),
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
	 */
	#[Override]
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_DISPLAY => [
				self::RELATIONSHIP_DATA => $resource->getDisplay(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_DATA_SOURCES => [
				self::RELATIONSHIP_DATA => $resource->getDataSources(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_TABS => [
				self::RELATIONSHIP_DATA => $resource->getTabs(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
			self::RELATIONSHIPS_GROUPS => [
				self::RELATIONSHIP_DATA => $resource->getGroups(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_DISPLAY) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_WIDGET_DISPLAY,
					[
						Router\ApiRoutes::URL_WIDGET_ID => $resource->getId()->toString(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_DATA_SOURCES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_WIDGET_DATA_SOURCES,
					[
						Router\ApiRoutes::URL_WIDGET_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => count($resource->getDataSources()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_DISPLAY
			|| $name === self::RELATIONSHIPS_DATA_SOURCES
			|| $name === self::RELATIONSHIPS_TABS
			|| $name === self::RELATIONSHIPS_GROUPS
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_WIDGET_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getId()->toString(),
						Router\ApiRoutes::RELATION_ENTITY => $name,
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
