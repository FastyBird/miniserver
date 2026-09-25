<?php declare(strict_types = 1);

/**
 * Trigger.php
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

use FastyBird\Core\Api\Schemas as ApiSchemas;
use FastyBird\Core\Routing as SlimRouterRouting;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use Neomerx\JsonApi;
use function count;

/**
 * Base trigger entity schema
 *
 * @template T of Entities\Triggers\Trigger
 * @extends  ApiSchemas\JsonApiSchema<T>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 */
abstract class Trigger extends ApiSchemas\JsonApiSchema
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_ACTIONS = 'actions';

	public const RELATIONSHIPS_NOTIFICATIONS = 'notifications';

	public function __construct(
		protected readonly SlimRouterRouting\IRouter $router,
		private readonly Models\States\ActionsRepository $actionStateRepository,
	)
	{
	}

	/**
	 * @param T $resource
	 *
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
			$isTriggered = false;

			if (count($resource->getActions()) > 0) {
				$isTriggered = true;

				foreach ($resource->getActions() as $action) {
					$state = $this->actionStateRepository->findOne($action);

					if ($state === null || $state->isTriggered() === false) {
						$isTriggered = false;
					}
				}
			}
		} catch (Exceptions\NotImplemented) {
			$isTriggered = null;
		}

		return [
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),
			'enabled' => $resource->isEnabled(),

			'is_triggered' => $isTriggered,
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
				Triggers\Constants::ROUTE_NAME_TRIGGER,
				[
					Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
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
			self::RELATIONSHIPS_ACTIONS => [
				self::RELATIONSHIP_DATA => $resource->getActions(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_NOTIFICATIONS => [
				self::RELATIONSHIP_DATA => $resource->getNotifications(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACTIONS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Triggers\Constants::ROUTE_NAME_TRIGGER_ACTIONS,
					[
						Router\ApiRoutes::URL_TRIGGER_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getActions()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_NOTIFICATIONS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Triggers\Constants::ROUTE_NAME_TRIGGER_NOTIFICATIONS,
					[
						Router\ApiRoutes::URL_TRIGGER_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getNotifications()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
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
		if (
			$name === self::RELATIONSHIPS_ACTIONS
			|| $name === self::RELATIONSHIPS_NOTIFICATIONS
		) {
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
