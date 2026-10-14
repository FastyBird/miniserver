<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Schemas\Channels\Properties;

use Exception;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Types;
use Neomerx\JsonApi;
use Override;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use TypeError;
use ValueError;
use function array_merge;
use function assert;
use function count;

/**
 * Channel property entity schema
 *
 * @template T of Entities\Channels\Properties\Dynamic
 * @extends Property<T>
 */
final class Dynamic extends Property
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::DEVICES->value . '/property/channel/' . Types\PropertyType::DYNAMIC->value;

	public function __construct(
		Routing\Router $router,
		Models\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
		parent::__construct($router, $channelsPropertiesRepository);
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\Properties\Dynamic::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, (string|bool|int|float|array<string>|array<int, (int|float|array<int, (string|int|float|null)>|null)>|array<int, array<int, (string|array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 * @throws InvalidArgumentException
	 */
	#[Override]
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'settable' => $resource->isSettable(),
			'queryable' => $resource->isQueryable(),
		]);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @throws PersistenceExceptions\Query
	 * @throws Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getRelationships($resource, $context), [
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_STATE => [
				self::RELATIONSHIP_DATA => $this->getState($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		]);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Runtime
	 */
	#[Override]
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_CHANNEL_ID => $resource->getChannel()->getId()->toString(),
						Router\ApiRoutes::URL_PROPERTY_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => count($resource->getChildren()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_STATE) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_CHANNEL_PROPERTY_STATE,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_CHANNEL_ID => $resource->getChannel()->getId()->toString(),
						Router\ApiRoutes::URL_PROPERTY_ID => $resource->getId()->toString(),
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
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Runtime
	 */
	#[Override]
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_CHILDREN
			|| $name === self::RELATIONSHIPS_STATE
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_CHANNEL_ID => $resource->getChannel()->getId()->toString(),
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function getState(
		Entities\Channels\Properties\Dynamic $property,
	): Documents\States\Channels\Properties\Property|null
	{
		$configuration = $this->channelsPropertiesConfigurationRepository->find($property->getId());
		assert($configuration instanceof Documents\Channels\Properties\Dynamic);

		return $this->channelPropertiesStatesManager->readState($configuration);
	}

}
