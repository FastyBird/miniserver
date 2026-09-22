<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

/**
 * Error object interface
 */
interface IErrorObject
{

	public function getId(): string|null;

	public function hasLinks(): bool;

	public function getLinks(): ILinkObjectCollection;

	public function getStatus(): int|null;

	public function getCode(): string|null;

	public function getTitle(): string|null;

	public function getDetail(): string|null;

	public function getSource(): ISourceObject|null;

	public function hasMeta(): bool;

	public function getMeta(): IMetaObjectCollection;

}
