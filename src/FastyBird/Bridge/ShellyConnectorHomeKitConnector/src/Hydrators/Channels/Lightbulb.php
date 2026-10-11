<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Color lightbulb channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Lightbulb>
 */
class Lightbulb extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\Lightbulb::class;
	}

}
