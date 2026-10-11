<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Writers;

use FastyBird\Connector\Modbus\Documents;

/**
 * System event device state periodic writer factory
 */
interface EventFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Event;

}
