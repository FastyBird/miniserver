<?php declare(strict_types = 1);

/**
 * Number.php
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
use function is_scalar;

/**
 * Entity numeric field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NumberField extends Field
{

	public function __construct(
		private readonly bool $isDecimal,
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
	 * @param  JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): float|int|null
	{
		$value = $attributes->get($this->getMappedName());

		return $value !== null && is_scalar($value) ? ($this->isDecimal ? (float) $value : (int) $value) : null;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
