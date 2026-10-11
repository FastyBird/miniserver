<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Relay channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Relay>
 */
final class Relay extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\Relay::class;
	}

}
