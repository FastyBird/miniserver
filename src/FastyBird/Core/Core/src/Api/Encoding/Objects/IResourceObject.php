<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

/**
 * Resource interface
 */
interface IResourceObject
{

	public function getId(): string|null;

	public function getType(): string;

	public function hasAttributes(): bool;

	public function getAttributes(): IStandardObject;

	public function hasRelationships(): bool;

	public function getRelationships(): IRelationshipObjectCollection;

	public function hasLinks(): bool;

	public function getLinks(): ILinkObjectCollection;

	public function hasMeta(): bool;

	public function getMeta(): IMetaObjectCollection;

}
