<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Schemas;

use FastyBird\Core\Exceptions;
use Neomerx\JsonApi\Contracts;
use Neomerx\JsonApi\Schema;
use Override;
use function method_exists;
use function property_exists;

/**
 * Entity schema constructor
 *
 * @template     T of object
 * @implements   Contracts\Schema\SchemaInterface<T>
 */
abstract class JsonApiSchema implements Contracts\Schema\SchemaInterface
{

	private string|null $subUrl = null;

	abstract public function getEntityClass(): string;

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationships($resource, Contracts\Schema\ContextInterface $context): iterable
	{
		return [];
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, Contracts\Schema\LinkInterface>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getLinks($resource): iterable
	{
		return [
			Contracts\Schema\BaseLinkInterface::SELF => $this->getSelfLink($resource),
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getSelfLink($resource): Contracts\Schema\LinkInterface
	{
		return new Schema\Link(true, $this->getSelfSubUrl($resource), false);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	private function getSelfSubUrl($resource): string
	{
		return $this->getResourcesSubUrl() . '/' . $this->getId($resource);
	}

	/**
	 * Get resources sub-URL
	 */
	private function getResourcesSubUrl(): string
	{
		if ($this->subUrl === null) {
			$this->subUrl = '/' . $this->getType();
		}

		return $this->subUrl;
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getId($resource): string|null
	{
		if (method_exists($resource, 'getId')) {
			return (string) $resource->getId();
		} elseif (property_exists($resource, 'id')) {
			return (string) $resource->id;
		}

		return null;
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationshipSelfLink($resource, string $name): Contracts\Schema\LinkInterface
	{
		// Feel free to override this method to change default URL or add meta
		$url = $this->getSelfSubUrl(
			$resource,
		) . '/' . Contracts\Schema\DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $name;

		return new Schema\Link(true, $url, false);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getRelationshipRelatedLink($resource, string $name): Contracts\Schema\LinkInterface
	{
		// Feel free to override this method to change default URL or add meta
		$url = $this->getSelfSubUrl($resource) . '/' . $name;

		return new Schema\Link(true, $url, false);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function hasIdentifierMeta($resource): bool
	{
		return false;
	}

	/**
	 * @param T $resource
	 *
	 * @throws Exceptions\Logic
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getIdentifierMeta($resource): mixed
	{
		throw new Exceptions\Logic('Default schema does not provide any meta');
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function hasResourceMeta($resource): bool
	{
		return false;
	}

	/**
	 * @param T $resource
	 *
	 * @throws Exceptions\Logic
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	#[Override]
	public function getResourceMeta($resource): mixed
	{
		throw new Exceptions\Logic('Default schema does not provide any meta');
	}

	#[Override]
	public function isAddSelfLinkInRelationshipByDefault(string $relationshipName): bool
	{
		return true;
	}

	#[Override]
	public function isAddRelatedLinkInRelationshipByDefault(string $relationshipName): bool
	{
		return true;
	}

}
