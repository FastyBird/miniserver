<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Devices\Properties;

use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Device property entity hydrator
 *
 * @extends Property<Entities\Devices\Properties\Dynamic>
 */
final class Dynamic extends Property
{

	/**
	 * @return class-string<Entities\Devices\Properties\Dynamic>
	 */
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Devices\Properties\Dynamic::class;
	}

}
