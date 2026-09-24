<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

/**
 * Relationship object interface
 */
interface IRelationshipObject
{

	public function hasLinks(): bool;

	/**
	 * @phpstan-return ILinkObjectCollection
	 *
	 * @return ILinkObjectCollection<string, ILinkObject|string>
	 */
	public function getLinks(): ILinkObjectCollection;

	public function hasData(): bool;

	/**
	 * @phpstan-return IResourceIdentifierCollection<int, IResourceIdentifierObject>|IResourceIdentifierObject|null
	 */
	public function getData(): IResourceIdentifierCollection|IResourceIdentifierObject|null;

	public function hasMeta(): bool;

	/**
	 * @phpstan-return IMetaObjectCollection<string, IMetaObject>
	 */
	public function getMeta(): IMetaObjectCollection;

	public function isHasMany(): bool;

	public function isHasOne(): bool;

	/**
	 * @phpstan-return IResourceIdentifierCollection<int, IResourceIdentifierObject>
	 */
	public function getIdentifiers(): IResourceIdentifierCollection;

	public function hasIdentifier(): bool;

	public function getIdentifier(): IResourceIdentifierObject;

}
