<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Schemas\Roles;

use Exception;
use FastyBird\Core\Api\Schemas;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Security\Models\Policies;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use Neomerx\JsonApi;
use Override;
use function count;

/**
 * Role entity schema
 *
 * @template T of Entities\Roles\Role
 * @extends  Schemas\JsonApiSchema<T>
 */
final class Role extends Schemas\JsonApiSchema
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::ACCOUNTS->value . '/role';

	/**
	 * Define relationships names
	 */
	public const string RELATIONSHIPS_PARENT = 'parent';

	public const string RELATIONSHIPS_CHILDREN = 'children';

	public function __construct(
		private readonly Policies\Repository $policiesRepository,
		private readonly Routing\Router $router,
	)
	{
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Roles\Role::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, string|bool>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Router\ApiRoutes::ROUTE_NAME_ROLE,
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
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$relationships = [
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];

		if ($resource->getParent() !== null) {
			$relationships[self::RELATIONSHIPS_PARENT] = [
				self::RELATIONSHIP_DATA => $resource->getParent(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			];
		}

		return $relationships;
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PARENT && $resource->getParent() !== null) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_ROLE,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getParent()->getId()->toString(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_ROLE_CHILDREN,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getId()->toString(),
					],
				),
				true,
				[
					'count' => count($resource->getChildren()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_CHILDREN
			|| ($name === self::RELATIONSHIPS_PARENT && $resource->getParent() !== null)
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_ROLE_RELATIONSHIP,
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
	 * @return array<Entities\Roles\Role>
	 *
	 * @throws Exception
	 */
	private function getChildren(Entities\Roles\Role $resource): array
	{
		$findQuery = new Queries\Entities\FindRoles();
		$findQuery->forParent($resource);

		return $this->policiesRepository->findAllBy(
			$findQuery,
			Entities\Roles\Role::class,
		);
	}

}
