<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Schemas\Triggers\Controls;

use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Core\Schemas\JsonApi as JsonApiSchemas;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use Neomerx\JsonApi;

/**
 * Trigger control entity schema
 *
 * @template T of Entities\Triggers\Controls\Control
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Control extends JsonApiSchemas\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/control/trigger';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_TRIGGER = 'trigger';

	public function __construct(private readonly SlimRouterRouting\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Triggers\Controls\Control::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'name' => $resource->getName(),
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Triggers\Constants::ROUTE_NAME_TRIGGER_CONTROL,
				[
					Router\ApiRoutes::URL_TRIGGER_ID => $resource->getTrigger()->getPlainId(),
					Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
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
			self::RELATIONSHIPS_TRIGGER => [
				self::RELATIONSHIP_DATA => $resource->getTrigger(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_TRIGGER) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Triggers\Constants::ROUTE_NAME_TRIGGER,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getTrigger()->getPlainId(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

}
