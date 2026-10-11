<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Writers;

use FastyBird\Connector\FbMqtt\Documents;

/**
 * Event bus exchange device state periodic writer factory
 */
interface ExchangeFactory extends WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Exchange;

}
