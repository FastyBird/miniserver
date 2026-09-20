<?php declare(strict_types = 1);

/**
 * Device.php
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

namespace FastyBird\Module\Devices\Schemas\Devices;

use DateTimeInterface;
use Exception;
use FastyBird\Core\Exceptions\DoctrineOrmQuery as DoctrineOrmQueryExceptions;
use FastyBird\Core\Schemas\JsonApi as JsonApiSchemas;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use Neomerx\JsonApi;

/**
 * Device entity schema
 *
 * @template T of Entities\Devices\Device
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Device extends JsonApiSchemas\JsonApi
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CHANNELS = 'channels';

	public const RELATIONSHIPS_PROPERTIES = 'properties';

	public const RELATIONSHIPS_CONTROLS = 'controls';

	public const RELATIONSHIPS_CONNECTOR = 'connector';

	public const RELATIONSHIPS_PARENTS = 'parents';

	public const RELATIONSHIPS_CHILDREN = 'children';

	public function __construct(
		protected readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly Models\Entities\Devices\Controls\ControlsRepository $devicesControlsRepository,
		protected readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly SlimRouterRouting\IRouter $router,
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
		return [
			'category' => $resource->getCategory()->value,
			'identifier' => $resource->getIdentifier(),
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),

			'owner' => $resource->getOwnerId(),
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
				Devices\Constants::ROUTE_NAME_DEVICE,
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
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_PROPERTIES => [
				self::RELATIONSHIP_DATA => $this->getProperties($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $this->getControls($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CHANNELS => [
				self::RELATIONSHIP_DATA => $this->getChannels($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONNECTOR => [
				self::RELATIONSHIP_DATA => $resource->getConnector(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_PARENTS => [
				self::RELATIONSHIP_DATA => $this->getParents($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PROPERTIES) {
			$findPropertiesQuery = new Queries\Entities\FindDeviceProperties();
			$findPropertiesQuery->forDevice($resource);

			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PROPERTIES,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => $this->devicesPropertiesRepository->getResultSet($findPropertiesQuery)->count(),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONTROLS) {
			$findControlsQuery = new Queries\Entities\FindDeviceControls();
			$findControlsQuery->forDevice($resource);

			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_CONTROLS,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => $this->devicesControlsRepository->getResultSet($findControlsQuery)->count(),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CHANNELS) {
			$findChannelsQuery = new Queries\Entities\FindChannels();
			$findChannelsQuery->forDevice($resource);

			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_CHANNELS,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => $this->channelsRepository->getResultSet($findChannelsQuery)->count(),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_PARENTS) {
			$findParentsQuery = new Queries\Entities\FindDevices();
			$findParentsQuery->forChild($resource);

			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PARENTS,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => $this->devicesRepository->getResultSet($findParentsQuery)->count(),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			$findParentsQuery = new Queries\Entities\FindDevices();
			$findParentsQuery->forParent($resource);

			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_CHILDREN,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => $this->devicesRepository->getResultSet($findParentsQuery)->count(),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONNECTOR) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CONNECTOR,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getConnector()->getId()->toString(),
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
			|| $name === self::RELATIONSHIPS_CHANNELS
			|| $name === self::RELATIONSHIPS_CHILDREN
			|| $name === self::RELATIONSHIPS_PARENTS
			|| $name === self::RELATIONSHIPS_CONNECTOR
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_RELATIONSHIP,
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

	/**
	 * @return array<Entities\Devices\Properties\Property>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	private function getProperties(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->forDevice($device);

		return $this->devicesPropertiesRepository->findAllBy($findQuery);
	}

	/**
	 * @return array<Entities\Devices\Controls\Control>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	private function getControls(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\Entities\FindDeviceControls();
		$findQuery->forDevice($device);

		return $this->devicesControlsRepository->findAllBy($findQuery);
	}

	/**
	 * @return array<Entities\Channels\Channel>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	private function getChannels(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\Entities\FindChannels();
		$findQuery->forDevice($device);

		return $this->channelsRepository->findAllBy($findQuery);
	}

	/**
	 * @return array<Entities\Devices\Device>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	private function getParents(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->forChild($device);

		return $this->devicesRepository->findAllBy($findQuery);
	}

	/**
	 * @return array<Entities\Devices\Device>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\Query
	 */
	private function getChildren(Entities\Devices\Device $device): array
	{
		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->forParent($device);

		return $this->devicesRepository->findAllBy($findQuery);
	}

}
