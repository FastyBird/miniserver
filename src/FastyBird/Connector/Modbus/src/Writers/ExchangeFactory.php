<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Writers;

use FastyBird\Connector\Modbus\Documents;

/**
 * Event bus exchange device state periodic writer factory
 */
interface ExchangeFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Exchange;

}
