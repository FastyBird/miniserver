<?php declare(strict_types = 1);

/**
 * Automatic.php
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

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\SlimRouter\Routing;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use Neomerx\JsonApi;
use function array_merge;
use function count;

/**
 * Automatic trigger entity schema
 *
 * @template T of Entities\Triggers\Automatic
 * @extends  Trigger<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Automatic extends Trigger
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::TRIGGERS->value . '/trigger/' . Entities\Triggers\Automatic::TYPE;

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CONDITIONS = 'conditions';

	public function __construct(
		Routing\IRouter $router,
		Models\States\ActionsRepository $actionStateRepository,
		private readonly Models\States\ConditionsRepository $conditionStateRepository,
	)
	{
		parent::__construct($router, $actionStateRepository);
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Triggers\Automatic::class;
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
		try {
			$isFulfilled = false;

			if (count($resource->getConditions()) > 0) {
				$isFulfilled = true;

				foreach ($resource->getConditions() as $condition) {
					$state = $this->conditionStateRepository->findOne($condition);

					if ($state === null || $state->isFulfilled() === false) {
						$isFulfilled = false;
					}
				}
			}
		} catch (Exceptions\NotImplemented) {
			$isFulfilled = null;
		}

		return array_merge((array) parent::getAttributes($resource, $context), [
			'is_fulfilled' => $isFulfilled,
		]);
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
			self::RELATIONSHIPS_CONDITIONS => [
				self::RELATIONSHIP_DATA => $resource->getConditions(),
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
		if ($name === self::RELATIONSHIPS_CONDITIONS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Triggers\Constants::ROUTE_NAME_TRIGGER_CONDITIONS,
					[
						Router\ApiRoutes::URL_TRIGGER_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getConditions()),
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
		if ($name === self::RELATIONSHIPS_CONDITIONS) {
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
