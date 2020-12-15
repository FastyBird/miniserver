<?php declare(strict_types = 1);

namespace Neomerx\JsonApi\Contracts\Schema;

/**
 * @template T of object
 */
interface SchemaInterface
{

	/**
	 * @param T $resource
	 *
	 * @return string|null
	 */
	public function getId($resource): ?string;

	/**
	 * @param T $resource
	 * @param ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 */
	public function getAttributes($resource, ContextInterface $context): iterable;

	/**
	 * @param T $resource
	 * @param ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 */
	public function getRelationships($resource, ContextInterface $context): iterable;

	/**
	 * @param T $resource
	 *
	 * @return LinkInterface
	 */
	public function getSelfLink($resource): LinkInterface;

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, LinkInterface>
	 */
	public function getLinks($resource): iterable;

	/**
	 * @param T $resource
	 * @param string $name
	 *
	 * @return LinkInterface
	 */
	public function getRelationshipSelfLink($resource, string $name): LinkInterface;

	/**
	 * @param T $resource
	 * @param string $name
	 *
	 * @return LinkInterface
	 */
	public function getRelationshipRelatedLink($resource, string $name): LinkInterface;

	/**
	 * @param T $resource
	 *
	 * @return bool
	 */
	public function hasIdentifierMeta($resource): bool;

	/**
	 * @param T $resource
	 *
	 * @return mixed
	 */
	public function getIdentifierMeta($resource);

	/**
	 * @param T $resource
	 *
	 * @return bool
	 */
	public function hasResourceMeta($resource): bool;

	/**
	 * @param T $resource
	 *
	 * @return mixed
	 */
	public function getResourceMeta($resource);

}
