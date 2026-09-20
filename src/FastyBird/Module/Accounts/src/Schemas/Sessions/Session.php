<?php declare(strict_types = 1);

/**
 * Session.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Schemas\Sessions;

use DateTimeInterface;
use FastyBird\Core\Routing\SlimRouter as SlimRouterRouting;
use FastyBird\Core\Schemas\JsonApi as JsonApis;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Router;
use Neomerx\JsonApi;

/**
 * Session entity schema
 *
 * @template T of Entities\Tokens\AccessToken
 * @extends  JsonApis\JsonApi<T>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Session extends JsonApis\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::ACCOUNTS->value . '/session';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_ACCOUNT = 'account';

	public function __construct(private readonly SlimRouterRouting\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Tokens\AccessToken::class;
	}

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
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(Accounts\Constants::ROUTE_NAME_SESSION),
			false,
		);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, array<int, (Entities\Accounts\Account|bool)>>
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
	 * @throws Exceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACCOUNT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT,
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
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACCOUNT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_SESSION_RELATIONSHIP,
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
