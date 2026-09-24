<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

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
