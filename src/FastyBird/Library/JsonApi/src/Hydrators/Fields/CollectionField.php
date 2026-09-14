<?php declare(strict_types = 1);

/**
 * CollectionField.php
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

use FastyBird\Library\JsonApi\Exceptions;
use IPub\JsonAPIDocument;
use function sprintf;

/**
 * Entity entities collection field
 *
 * @package        FastyBird:JsonApi!
 * @subpackage     Hydrators
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CollectionField extends EntityField
{

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject<string, mixed> $attributes
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getValue(JsonAPIDocument\Objects\IStandardObject $attributes): mixed
	{
		throw new Exceptions\InvalidState(
			sprintf('Collection field \'%s\' could not be mapped as attribute.', $this->getMappedName()),
		);
	}

}
