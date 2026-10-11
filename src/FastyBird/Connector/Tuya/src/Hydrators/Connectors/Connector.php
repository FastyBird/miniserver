<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Hydrators\Connectors;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Tuya connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\Connectors\Connector>
 */
final class Connector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Connector::class;
	}

}
