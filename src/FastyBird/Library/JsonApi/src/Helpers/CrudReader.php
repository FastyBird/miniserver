<?php declare(strict_types = 1);

/**
 * CrudReader.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:JsonApi!
 * @subpackage     Helpers
 * @since          0.2.0
 *
 * @date           20.05.21
 */

namespace FastyBird\Library\JsonApi\Helpers;

use IPub\DoctrineCrud;
use ReflectionAttribute;
use ReflectionProperty;
use function array_reduce;
use function assert;

/**
 * Doctrine CRUD annotation reader
 *
 * @package            FastyBird:JsonApi!
 * @subpackage         Helpers
 *
 * @author             Adam Kadlec <adam.kadlec@fastybird.com>
 */
class CrudReader
{

	/**
	 * @return array<bool>
	 */
	public function read(ReflectionProperty $rp): array
	{
		$crudAttribute = array_reduce(
			$rp->getAttributes(),
			static function (ReflectionAttribute|null $carry, ReflectionAttribute $attribute): ReflectionAttribute|null {
				if ($carry === null && $attribute->getName() === DoctrineCrud\Mapping\Attribute\Crud::class) {
					return $attribute;
				}

				return $carry;
			},
		);

		if ($crudAttribute === null) {
			return [false, false];
		}

		$crud = $crudAttribute->newInstance();
		assert($crud instanceof DoctrineCrud\Mapping\Attribute\Crud);

		return [$crud->isRequired(), $crud->isWritable()];
	}

}
