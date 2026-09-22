<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\JsonApi\Objects;

use Override;

/**
 * Meta value
 */
final class MetaObject implements IMetaObject
{

	/**
	 * @param string|int|float|bool|array<mixed> $value
	 */
	public function __construct(private string|int|float|bool|array $value)
	{
	}

	#[Override]
	public function getValue(): string|int|float|bool|array
	{
		return $this->value;
	}

}
