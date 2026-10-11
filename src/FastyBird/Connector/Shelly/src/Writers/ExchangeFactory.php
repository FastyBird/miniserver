<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Writers;

use FastyBird\Connector\Shelly\Documents;

/**
 * Event bus exchange device state periodic writer factory
 */
interface ExchangeFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Exchange;

}
