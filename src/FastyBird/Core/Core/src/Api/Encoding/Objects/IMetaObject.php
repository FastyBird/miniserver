<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

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
