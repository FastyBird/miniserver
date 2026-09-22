<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use FastyBird\Core\Encoding\JsonApi;

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
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): mixed
	{
		return $attributes->get($this->getMappedName());
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
