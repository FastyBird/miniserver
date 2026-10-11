<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Writers;

use FastyBird\Connector\Tuya\Documents;

/**
 * Event bus exchange device state periodic writer factory
 */
interface ExchangeFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Exchange;

}
