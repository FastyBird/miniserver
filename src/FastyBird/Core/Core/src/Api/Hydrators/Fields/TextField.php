<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

use FastyBird\Core\Api\Encoding\Objects;
use function is_scalar;

/**
 * Entity text field
 */
final class TextField extends Field
{

	public function __construct(
		private readonly bool $isNullable,
		string $mappedName,
		string $fieldName,
		bool $isRequired,
		bool $isWritable,
	)
	{
		parent::__construct($mappedName, $fieldName, $isRequired, $isWritable);
	}

	/**
	 * @param Objects\IStandardObject<string, mixed> $attributes
	 */
	public function getValue(Objects\IStandardObject $attributes): string|null
	{
		$value = $attributes->get($this->getMappedName());

		return $value !== null && is_scalar($value)
			? ($this->isNullable && $value === '' ? null : (string) $value)
			: null;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
