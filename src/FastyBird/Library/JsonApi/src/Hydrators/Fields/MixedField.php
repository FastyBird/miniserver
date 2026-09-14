<?php declare(strict_types = 1);

/**
 * Mixed.php
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

use IPub\JsonAPIDocument;

/**
 * Entity mixed value field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
	 * @param JsonAPIDocument\Objects\IStandardObject<string, mixed> $attributes
	 */
	public function getValue(JsonAPIDocument\Objects\IStandardObject $attributes): mixed
	{
		return $attributes->get($this->getMappedName());
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

}
