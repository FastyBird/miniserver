<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Encoding\Objects;

/**
 * Meta value
 */
final class MetaObject
{

	/**
	 * @param string|int|float|bool|array<mixed> $value
	 */
	public function __construct(private string|int|float|bool|array $value)
	{
	}

	/**
	 * @return string|int|float|bool|array<mixed>
	 */
	public function getValue(): string|int|float|bool|array
	{
		return $this->value;
	}

}
