<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

use FastyBird\Core\Api\Encoding\Objects;

/**
 * Entity field
 */
abstract class Field
{

	public function __construct(
		private readonly string $mappedName,
		private readonly string $fieldName,
		private readonly bool $isRequired,
		private readonly bool $isWritable,
	)
	{
	}

	/**
	 * @param Objects\IStandardObject<string, mixed> $attributes
	 */
	abstract public function getValue(Objects\IStandardObject $attributes): mixed;

	public function getMappedName(): string
	{
		return $this->mappedName;
	}

	public function getFieldName(): string
	{
		return $this->fieldName;
	}

	public function isRequired(): bool
	{
		return $this->isRequired;
	}

	public function isWritable(): bool
	{
		return $this->isWritable;
	}

}
