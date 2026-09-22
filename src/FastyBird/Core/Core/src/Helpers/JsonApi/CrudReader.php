<?php declare(strict_types = 1);

namespace FastyBird\Core\Helpers\JsonApi;

use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use ReflectionAttribute;
use ReflectionProperty;
use function array_reduce;
use function assert;

/**
 * Reads a property's #[Crud] attribute to determine whether it is required and/or writable
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
				if ($carry === null && $attribute->getName() === IPubDoctrine\Crud::class) {
					return $attribute;
				}

				return $carry;
			},
		);

		if ($crudAttribute === null) {
			return [false, false];
		}

		$crud = $crudAttribute->newInstance();
		assert($crud instanceof IPubDoctrine\Crud);

		return [$crud->isRequired(), $crud->isWritable()];
	}

}
