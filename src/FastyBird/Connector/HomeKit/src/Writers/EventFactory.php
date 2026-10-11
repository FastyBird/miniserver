<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Writers;

use FastyBird\Connector\HomeKit\Documents;

/**
 * System event device state periodic writer factory
 */
interface EventFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Event;

}
