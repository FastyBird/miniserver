<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators\Connectors;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use Override;

/**
 * Zigbee2MQTT connector entity hydrator
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
