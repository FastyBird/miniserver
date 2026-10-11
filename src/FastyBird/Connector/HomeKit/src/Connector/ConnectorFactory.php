<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Connector;

use FastyBird\Connector\HomeKit\Connector;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;

/**
 * Connector service executor factory
 */
interface ConnectorFactory extends DevicesConnectors\ConnectorFactory
{

	#[Override]
	public function create(
		DevicesDocuments\Connectors\Connector $connector,
	): Connector\Connector;

}
