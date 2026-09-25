<?php declare(strict_types = 1);

namespace FastyBird\Core\Api\Hydrators\Fields;

use FastyBird\Core\Api\Encoding\Objects;
use FastyBird\Core\Exceptions;
use function sprintf;

/**
 * Entity entities collection field
 */
final class CollectionField extends EntityField
{

	/**
	 * @param Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(Objects\IStandardObject $attributes): mixed
	{
		throw new Exceptions\InvalidState(
			sprintf('Collection field \'%s\' could not be mapped as attribute.', $this->getMappedName()),
		);
	}

}
