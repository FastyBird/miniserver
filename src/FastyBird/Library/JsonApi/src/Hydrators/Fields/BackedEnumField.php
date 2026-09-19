<?php declare(strict_types = 1);

/**
 * BackedEnumField.php
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

use BackedEnum;
use FastyBird\Library\JsonApi;
use function call_user_func;
use function is_callable;

/**
 * Entity backed enum field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class BackedEnumField extends Field
{

	public function __construct(
		private readonly string $typeClass,
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
	public function getValue(JsonApi\Objects\IStandardObject $attributes): BackedEnum|null
	{
		$value = $attributes->get($this->getMappedName());

		$callable = [$this->typeClass, 'from'];

		if (is_callable($callable)) {
			$result = $value !== null ? call_user_func($callable, $value) : null;

			return $result instanceof BackedEnum ? $result : null;
		}

		return null;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
