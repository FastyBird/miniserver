<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Connectors;

use FastyBird\Module\Devices\Entities;

/**
 * Generic connector entity hydrator
 *
 * @extends Connector<Entities\Connectors\Generic>
 */
final class Generic extends Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Generic::class;
	}

}
