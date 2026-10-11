<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Writers;

use FastyBird\Connector\Viera\Documents;

/**
 * Event bus exchange device state periodic writer factory
 */
interface ExchangeFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Exchange;

}
