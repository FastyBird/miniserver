<?php declare(strict_types = 1);

/**
 * BooleanField.php
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
 * Entity boolean field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class BooleanField extends Field
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
	public function getValue(JsonApi\Objects\IStandardObject $attributes): bool|null
	{
		$value = $attributes->get($this->getMappedName());

		if ($value !== null) {
			return (bool) $value;
		} elseif ($this->isNullable) {
			return null;
		}

		return false;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
