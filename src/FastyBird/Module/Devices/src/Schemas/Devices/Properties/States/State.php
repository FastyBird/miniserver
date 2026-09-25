<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           24.01.24
 */

namespace FastyBird\Module\Devices\Schemas\Devices\Properties\States;

use DateTimeInterface;
use FastyBird\Core\Api\Schemas as ApiSchemas;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Neomerx\JsonApi;
use function assert;
use function is_bool;

/**
 * Device property state entity schema
 *
 * @template T of States\DeviceProperty
 * @extends  ApiSchemas\JsonApiSchema<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State extends ApiSchemas\JsonApiSchema
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::DEVICES->value . '/property/device/' . Types\PropertyType::DYNAMIC->value . '/state';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_PROPERTY = 'property';

	public function __construct(
		private readonly Routing\IRouter $router,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
	)
	{
	}

	public function getEntityClass(): string
	{
		return Documents\States\Devices\Properties\Property::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, (string|bool|int|float|array<string>|array<int, (int|float|array<int, (string|int|float|null)>|null)>|array<int, array<int, (string|array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @throws Exceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$property = $this->devicesPropertiesRepository->find($resource->getId());
		assert($property instanceof Entities\Devices\Properties\Property);

		return [
			'device' => $property->getDevice()->getId()->toString(),
			'actual_value' => Utilities\Value::flattenValue($resource->getActualValue()),
			'expected_value' => Utilities\Value::flattenValue($resource->getExpectedValue()),
			'pending' => is_bool($resource->getPending())
				? $resource->getPending()
				: $resource->getPending()->format(DateTimeInterface::ATOM),
			'is_valid' => $resource->isValid(),
			'created_at' => $resource->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $resource->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	/**
	 * @param T $resource
	 *
	 * @throws Exceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		$property = $this->devicesPropertiesRepository->find($resource->getId());
		assert($property instanceof Entities\Devices\Properties\Property);

		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_STATE,
				[
					Router\ApiRoutes::URL_DEVICE_ID => $property->getDevice()->getId()->toString(),
					Router\ApiRoutes::URL_ITEM_ID => $property->getId()->toString(),
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
	 * @throws Exceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$property = $this->devicesPropertiesRepository->find($resource->getId());
		assert($property instanceof Entities\Devices\Properties\Property);

		return [
			self::RELATIONSHIPS_PROPERTY => [
				self::RELATIONSHIP_DATA => $property->getDevice(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @throws Exceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		$property = $this->devicesPropertiesRepository->find($resource->getId());
		assert($property instanceof Entities\Devices\Properties\Property);

		if ($name === self::RELATIONSHIPS_PROPERTY) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $property->getDevice()->getId()->toString(),
						Router\ApiRoutes::URL_ITEM_ID => $property->getId()->toString(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

}
