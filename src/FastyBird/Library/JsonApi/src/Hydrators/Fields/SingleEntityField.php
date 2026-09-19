<?php declare(strict_types = 1);

/**
 * SingleEntityField.php
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
use FastyBird\Library\JsonApi\Exceptions;
use function is_array;
use function sprintf;

/**
 * Entity one to one relation entity field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SingleEntityField extends EntityField
{

	/**
	 * @param JsonApi\Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @return array<mixed>|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(JsonApi\Objects\IStandardObject $attributes): array|null
	{
		if ($this->isRelationship()) {
			throw new Exceptions\InvalidState(
				sprintf('Single entity field \'%s\' could not be mapped as attribute.', $this->getMappedName()),
			);
		}

		$value = $attributes->get($this->getMappedName());

		if ($value instanceof JsonApi\Objects\IStandardObject) {
			$value = $value->toArray();
		}

		if (is_array($value) && $value !== []) {
			$value['entity'] = $this->getClassName();

		} elseif ($this->isNullable()) {
			return null;
		}

		return is_array($value) ? $value : null;
	}

}
