<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Module\Devices\Schemas\Channels\Controls;

use DateTimeInterface;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Core\Schemas\JsonApi as JsonApiSchemas;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use Neomerx\JsonApi;

/**
 * Channel control entity schema
 *
 * @template T of Entities\Channels\Controls\Control
 * @extends JsonApiSchemas\JsonApi<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Control extends JsonApiSchemas\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::DEVICES->value . '/control/channel';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CHANNEL = 'channel';

	public function __construct(private readonly SlimRouterRouting\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Channels\Controls\Control::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
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
		return [
			'name' => $resource->getName(),
			'owner' => $resource->getChannel()->getDevice()->getOwnerId(),
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
				Devices\Constants::ROUTE_NAME_CHANNEL_CONTROL,
				[
					Router\ApiRoutes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getId()->toString(),
					Router\ApiRoutes::URL_CHANNEL_ID => $resource->getChannel()->getId()->toString(),
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
			self::RELATIONSHIPS_CHANNEL => [
				self::RELATIONSHIP_DATA => $resource->getChannel(),
				self::RELATIONSHIP_LINKS_SELF => false,
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
		if ($name === self::RELATIONSHIPS_CHANNEL) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_ITEM_ID => $resource->getChannel()->getId()->toString(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

}
