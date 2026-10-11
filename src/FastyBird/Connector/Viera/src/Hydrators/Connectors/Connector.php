<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Hydrators\Connectors;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use Override;

/**
 * Viera connector entity hydrator
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
