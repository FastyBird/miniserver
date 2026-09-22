<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

/**
 * Link value interface
 */
interface ILinkObject
{

	public function getHref(): string;

	public function hasMeta(): bool;

	/**
	 * @phpstan-return IMetaObjectCollection<string, IMetaObject>
	 */
	public function getMeta(): IMetaObjectCollection;

}
