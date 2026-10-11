<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Connectors\Properties;

use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Connector property entity hydrator
 *
 * @extends Property<Entities\Connectors\Properties\Dynamic>
 */
final class Dynamic extends Property
{

	/**
	 * @return class-string<Entities\Connectors\Properties\Dynamic>
	 */
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Connectors\Properties\Dynamic::class;
	}

}
