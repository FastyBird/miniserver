<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Devices\Properties;

use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Device property entity hydrator
 *
 * @extends Property<Entities\Devices\Properties\Mapped>
 */
final class Mapped extends Property
{

	/**
	 * @return class-string<Entities\Devices\Properties\Mapped>
	 */
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Devices\Properties\Mapped::class;
	}

}
