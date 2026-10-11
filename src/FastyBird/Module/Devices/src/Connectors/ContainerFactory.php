<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Connectors;

use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use FastyBird\Module\Devices\Documents;

/**
 * Connector service executor factory
 */
interface ContainerFactory extends DevicesConnectors\ConnectorFactory
{

	public function create(Documents\Connectors\Connector $connector): Container;

}
