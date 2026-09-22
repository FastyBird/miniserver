<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use FastyBird\Core\Encoding\JsonApi;
use Nette;

/**
 * Entity field
 */
abstract class Field
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $mappedName,
		private readonly string $fieldName,
		private readonly bool $isRequired,
		private readonly bool $isWritable,
	)
	{
	}

	/**
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 */
	abstract public function getValue(JsonApi\Objects\IStandardObject $attributes): mixed;

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
