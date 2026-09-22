<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

/**
 * Meta value interface
 */
interface IMetaObject
{

	/**
	 * @return string|int|float|bool|array<mixed>
	 */
	public function getValue(): string|int|float|bool|array;

}
