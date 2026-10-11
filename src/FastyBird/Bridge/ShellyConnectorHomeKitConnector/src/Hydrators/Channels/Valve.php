<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Valve channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Valve>
 */
class Valve extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\Valve::class;
	}

}
