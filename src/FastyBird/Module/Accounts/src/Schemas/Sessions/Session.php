<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Schemas\Sessions;

use DateTimeInterface;
use FastyBird\Core\Api\Schemas;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions as AccountsExceptions;
use FastyBird\Module\Accounts\Router;
use Neomerx\JsonApi;
use Override;

/**
 * Session entity schema
 *
 * @template T of Entities\Tokens\AccessToken
 * @extends  Schemas\JsonApiSchema<T>
 */
final class Session extends Schemas\JsonApiSchema
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::ACCOUNTS->value . '/session';

	/**
	 * Define relationships names
	 */
	public const string RELATIONSHIPS_ACCOUNT = 'account';

	public function __construct(private readonly Routing\Router $router)
	{
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Tokens\AccessToken::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, string|null>
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
			'token' => $resource->getToken(),
			'expiration' => $resource->getValidTill()?->format(DateTimeInterface::ATOM),
			'token_type' => 'Bearer',
			'refresh' => $resource->getRefreshToken()?->getToken(),
		];
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
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(Router\ApiRoutes::ROUTE_NAME_SESSION),
			false,
		);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, array<int, (Entities\Accounts\Account|bool)>>
	 *
	 * @throws AccountsExceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_ACCOUNT => [
				self::RELATIONSHIP_DATA => $resource->getIdentity()->getAccount(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\Runtime
	 * @throws AccountsExceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACCOUNT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_ACCOUNT,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getIdentity()
							->getAccount()
							->getId()->toString(),
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
		if ($name === self::RELATIONSHIPS_ACCOUNT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Router\ApiRoutes::ROUTE_NAME_SESSION_RELATIONSHIP,
					[
						Router\ApiRoutes::RELATION_ENTITY => $name,
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
