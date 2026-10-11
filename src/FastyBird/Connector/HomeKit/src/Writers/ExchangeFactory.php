<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Writers;

use FastyBird\Connector\HomeKit\Documents;
use Override;

/**
 * Event bus exchange device state periodic writer factory
 */
interface ExchangeFactory extends WriterFactory
{

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Exchange;

}
