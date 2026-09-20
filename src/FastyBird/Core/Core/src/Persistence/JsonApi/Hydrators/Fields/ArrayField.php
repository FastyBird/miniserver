<?php declare(strict_types = 1);

/**
 * ArrayField.php
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

namespace FastyBird\Core\Persistence\JsonApi\Hydrators\Fields;

use FastyBird\Core\Encoding\JsonApi;

/**
 * Entity array field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ArrayField extends Field
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
	 *
	 * @return array<mixed>|null
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): array|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value instanceof JsonApi\Objects\IStandardObject) {
			return $value->toArray();
		}

		return $value === null ? ($this->isNullable ? [] : null) : (array) $value;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
