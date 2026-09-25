<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

/**
 * Entity field
 */
abstract class EntityField extends Field
{

	/**
	 * @param class-string $className
	 */
	public function __construct(
		private string $className,
		private readonly bool $nullable,
		string $mappedName,
		private readonly bool $isRelationship,
		string $fieldName,
		bool $isRequired,
		bool $isWritable,
	)
	{
		parent::__construct($mappedName, $fieldName, $isRequired, $isWritable);
	}

	/**
	 * @return class-string
	 */
	public function getClassName(): string
	{
		return $this->className;
	}

	public function isNullable(): bool
	{
		return $this->nullable;
	}

	public function isRelationship(): bool
	{
		return $this->isRelationship;
	}

}
