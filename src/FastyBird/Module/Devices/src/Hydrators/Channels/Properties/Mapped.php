<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Channels\Properties;

use FastyBird\Module\Devices\Entities;

/**
 * Channel property entity hydrator
 *
 * @extends  Property<Entities\Channels\Properties\Mapped>
 */
final class Mapped extends Property
{

	/**
	 * @return class-string<Entities\Channels\Properties\Mapped>
	 */
	public function getEntityName(): string
	{
		return Entities\Channels\Properties\Mapped::class;
	}

}
