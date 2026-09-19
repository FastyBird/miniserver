<?php declare(strict_types = 1);

/**
 * Field.php
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

use FastyBird\Library\JsonApi;
use Nette;

/**
 * Entity field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
