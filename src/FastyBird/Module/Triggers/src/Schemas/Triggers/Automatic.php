<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Schemas\Triggers;

use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions as TriggersExceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use Neomerx\JsonApi;
use Override;
use function array_merge;
use function count;

/**
 * Automatic trigger entity schema
 *
 * @template T of Entities\Triggers\Automatic
 * @extends  Trigger<T>
 */
final class Automatic extends Trigger
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::TRIGGERS->value . '/trigger/' . Entities\Triggers\Automatic::TYPE;

	/**
	 * Define relationships names
	 */
	public const string RELATIONSHIPS_CONDITIONS = 'conditions';

	public function __construct(
		Routing\Router $router,
		Models\States\ActionsRepository $actionStateRepository,
		private readonly Models\States\ConditionsRepository $conditionStateRepository,
	)
	{
		parent::__construct($router, $actionStateRepository);
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Triggers\Automatic::class;
	}

	/**
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
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
		} catch (TriggersExceptions\NotImplemented) {
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
	#[Override]
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Runtime
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
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
