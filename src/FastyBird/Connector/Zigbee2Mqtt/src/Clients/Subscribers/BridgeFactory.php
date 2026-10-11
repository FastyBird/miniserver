<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Clients\Subscribers;

use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Entities;

/**
 * Bridge subscriber factory
 */
interface BridgeFactory
{

	public function create(
		Documents\Connectors\Connector|Entities\Connectors\Connector $connector,
	): Bridge;

}
