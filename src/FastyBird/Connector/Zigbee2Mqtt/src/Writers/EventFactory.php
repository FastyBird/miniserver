<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Writers;

use FastyBird\Connector\Zigbee2Mqtt\Documents;

/**
 * System event device state periodic writer factory
 */
interface EventFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Event;

}
