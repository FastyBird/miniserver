<?php declare(strict_types = 1);

/**
 * IRelationshipObject.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 * @since          0.2.0
 *
 * @date           19.05.21
 */

namespace FastyBird\Core\Encoding\JsonApi\Objects;

/**
 * Relationship object interface
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
