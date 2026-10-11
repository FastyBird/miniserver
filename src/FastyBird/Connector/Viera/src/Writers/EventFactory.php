<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Writers;

use FastyBird\Connector\Viera\Documents;

/**
 * System event device state periodic writer factory
 */
interface EventFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Event;

}
