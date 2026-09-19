<?php declare(strict_types = 1);

/**
 * IResourceObject.php
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

namespace FastyBird\Library\JsonApi\Objects;

use FastyBird\Library\JsonApi\Objects;

/**
 * Resource interface
 *
 * @package        iPublikuj:JsonAPIDocument!
 * @subpackage     Objects
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IResourceObject
{

	public function getId(): string|null;

	public function getType(): string;

	public function hasAttributes(): bool;

	public function getAttributes(): Objects\IStandardObject;

	public function hasRelationships(): bool;

	public function getRelationships(): IRelationshipObjectCollection;

	public function hasLinks(): bool;

	public function getLinks(): ILinkObjectCollection;

	public function hasMeta(): bool;

	public function getMeta(): IMetaObjectCollection;

}
