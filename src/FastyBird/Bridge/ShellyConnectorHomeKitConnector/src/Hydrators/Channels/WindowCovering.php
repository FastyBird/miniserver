<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Window covering channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\WindowCovering>
 */
final class WindowCovering extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\WindowCovering::class;
	}

}
