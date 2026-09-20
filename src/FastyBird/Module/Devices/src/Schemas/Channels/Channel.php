<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\Schemas\Channels;

use DateTimeInterface;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Core\Schemas\JsonApi as JsonApiSchemas;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use Neomerx\JsonApi;
use function count;

/**
 * Channel entity schema
 *
 * @template T of Entities\Channels\Channel
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Channel extends JsonApiSchemas\JsonApi
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_DEVICE = 'device';

	public const RELATIONSHIPS_PROPERTIES = 'properties';

	public const RELATIONSHIPS_CONTROLS = 'controls';

	public function __construct(private readonly SlimRouterRouting\IRouter $router)
	{
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, (string|array<string>|null)>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'category' => $resource->getCategory()->value,
			'identifier' => $resource->getIdentifier(),
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),
			'owner' => $resource->getDevice()->getOwnerId(),
			'created_at' => $resource->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $resource->getUpdatedAt()?->format(DateTimeInterface::ATOM),
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
				Devices\Constants::ROUTE_NAME_CHANNEL,
				[
					Router\ApiRoutes::URL_DEVICE_ID => $resource->getDevice()->getId()->toString(),
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
			self::RELATIONSHIPS_DEVICE => [
				self::RELATIONSHIP_DATA => $resource->getDevice(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_PROPERTIES => [
				self::RELATIONSHIP_DATA => $resource->getProperties(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $resource->getControls(),
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
		if ($name === self::RELATIONSHIPS_PROPERTIES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTIES,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_CHANNEL_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => count($resource->getProperties()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_CONTROLS,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_CHANNEL_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => count($resource->getControls()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_DEVICE) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getDevice()->getId()->toString(),
					],
				),
				false,
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
			$name === self::RELATIONSHIPS_PROPERTIES
			|| $name === self::RELATIONSHIPS_CONTROLS
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getDevice()->getId()->toString(),
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
