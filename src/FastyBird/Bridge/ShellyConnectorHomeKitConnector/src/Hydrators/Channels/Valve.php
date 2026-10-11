<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use Override;

/**
 * Valve channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Valve>
 */
final class Valve extends Shelly
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Valve::class;
	}

}
