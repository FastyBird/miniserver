<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Library\JsonApi\Hydrators\Fields;

/**
 * Entity field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
