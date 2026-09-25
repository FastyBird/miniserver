<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

use FastyBird\Core\Api\Encoding\Objects;

/**
 * Entity mixed value field
 */
final class MixedField extends Field
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
	public function getValue(Objects\IStandardObject $attributes): mixed
	{
		return $attributes->get($this->getMappedName());
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
