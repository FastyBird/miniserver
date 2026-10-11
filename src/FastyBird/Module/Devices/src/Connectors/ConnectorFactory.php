<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Connectors;

use FastyBird\Module\Devices\Documents;

/**
 * Connector factory interface
 */
interface ConnectorFactory
{

	public function create(Documents\Connectors\Connector $connector): Connector;

}
