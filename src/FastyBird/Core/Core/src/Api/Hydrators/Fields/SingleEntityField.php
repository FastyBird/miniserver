<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Exceptions;
use function is_array;
use function sprintf;

/**
 * Entity one to one relation entity field
 */
final class SingleEntityField extends EntityField
{

	/**
	 * @param Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @return array<mixed>|null
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(Objects\IStandardObject $attributes): array|null
	{
		if ($this->isRelationship()) {
			throw new Exceptions\InvalidState(
				sprintf('Single entity field \'%s\' could not be mapped as attribute.', $this->getMappedName()),
			);
		}

		$value = $attributes->get($this->getMappedName());

		if ($value instanceof Objects\IStandardObject) {
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
