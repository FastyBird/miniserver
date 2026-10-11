<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use Override;

/**
 * Outlet channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\Outlet>
 */
final class Outlet extends Shelly
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Outlet::class;
	}

}
