<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Hydrators\Connectors;

use FastyBird\Connector\Modbus\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use Override;

/**
 * Modbus connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\Connectors\Connector>
 */
final class Connector extends DevicesHydrators\Connectors\Connector
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Connectors\Connector::class;
	}

}
