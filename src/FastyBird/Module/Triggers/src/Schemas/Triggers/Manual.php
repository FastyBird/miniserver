<?php declare(strict_types = 1);

/**
 * Manual.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Schemas\Triggers;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Router;
use Neomerx\JsonApi;
use function array_merge;
use function count;

/**
 * Manual trigger entity schema
 *
 * @template T of Entities\Triggers\Manual
 * @extends  Trigger<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Manual extends Trigger
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/trigger/' . Entities\Triggers\Manual::TYPE;

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CONTROLS = 'controls';

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Triggers\Manual::class;
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
		return array_merge([
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $resource->getControls(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		], (array) parent::getRelationships($resource, $context));
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Triggers\Constants::ROUTE_NAME_TRIGGER_CONTROLS,
					[
						Router\ApiRoutes::URL_TRIGGER_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getControls()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Triggers\Constants::ROUTE_NAME_TRIGGER_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
						Router\ApiRoutes::RELATION_ENTITY => $name,
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
