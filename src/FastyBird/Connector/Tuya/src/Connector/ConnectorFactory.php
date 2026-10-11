<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Connector;

use FastyBird\Connector\Tuya\Connector;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

/**
 * Connector service executor factory
 */
interface ConnectorFactory extends DevicesConnectors\ConnectorFactory
{

	public function create(
		DevicesDocuments\Connectors\Connector $connector,
	): Connector\Connector;

}
