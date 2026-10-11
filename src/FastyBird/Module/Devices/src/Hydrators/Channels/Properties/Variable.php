<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Channels\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Channel property entity hydrator
 *
 * @extends Property<Entities\Channels\Properties\Variable>
 */
final class Variable extends Property
{

	/**
	 * @return class-string<Entities\Channels\Properties\Variable>
	 */
	public function getEntityName(): string
	{
		return Entities\Channels\Properties\Variable::class;
	}

}
