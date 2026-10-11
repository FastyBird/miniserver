<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Devices\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Device property entity hydrator
 *
 * @extends Property<Entities\Devices\Properties\Variable>
 */
final class Variable extends Property
{

	/**
	 * @return class-string<Entities\Devices\Properties\Variable>
	 */
	public function getEntityName(): string
	{
		return Entities\Devices\Properties\Variable::class;
	}

}
