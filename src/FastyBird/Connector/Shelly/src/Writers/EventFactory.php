<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Writers;

use FastyBird\Connector\Shelly\Documents;
use Override;

/**
 * System event device state periodic writer factory
 */
interface EventFactory extends WriterFactory
{

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Event;

}
