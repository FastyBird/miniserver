<?php declare(strict_types = 1);

/**
 * Tab.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           03.08.24
 */

namespace FastyBird\Module\Ui\Schemas\Dashboards\Tabs;

use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Core\Schemas\JsonApi as JsonApiSchemas;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Router;
use Neomerx\JsonApi;

/**
 * Tab entity schema
 *
 * @template T of Entities\Dashboards\Tabs\Tab
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package          FastyBird:UIModule!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Tab extends JsonApiSchemas\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::UI->value . '/tab';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_DASHBOARD = 'dashboard';

	public const RELATIONSHIPS_WIDGETS = 'widgets';

	public function __construct(protected readonly SlimRouterRouting\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Dashboards\Tabs\Tab::class;
	}

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
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'identifier' => $resource->getIdentifier(),
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),
			'priority' => $resource->getPriority(),
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Ui\Constants::ROUTE_NAME_DASHBOARD_TAB,
				[
					Router\ApiRoutes::URL_DASHBOARD_ID => $resource->getDashboard()->getId()->toString(),
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
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_DASHBOARD => [
				self::RELATIONSHIP_DATA => $resource->getDashboard(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
			self::RELATIONSHIPS_WIDGETS => [
				self::RELATIONSHIP_DATA => $resource->getWidgets(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_DASHBOARD) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Ui\Constants::ROUTE_NAME_DASHBOARD,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getDashboard()->getId()->toString(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_WIDGETS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Ui\Constants::ROUTE_NAME_DASHBOARD_TAB_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_DASHBOARD_ID => $resource->getDashboard()->getId()->toString(),
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
