<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Outlet channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Outlet>
 */
final class Outlet extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\Outlet::class;
	}

}
