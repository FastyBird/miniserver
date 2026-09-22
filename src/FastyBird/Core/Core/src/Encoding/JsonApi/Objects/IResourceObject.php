<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use FastyBird\Core\Encoding\JsonApi\Objects;

/**
 * Resource interface
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
