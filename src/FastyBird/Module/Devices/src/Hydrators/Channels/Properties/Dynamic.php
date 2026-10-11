<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Channels\Properties;

use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Channel property entity hydrator
 *
 * @extends Property<Entities\Channels\Properties\Dynamic>
 */
final class Dynamic extends Property
{

	/**
	 * @return class-string<Entities\Channels\Properties\Dynamic>
	 */
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Properties\Dynamic::class;
	}

}
