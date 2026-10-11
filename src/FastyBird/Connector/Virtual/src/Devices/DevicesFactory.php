<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Devices;

use FastyBird\Connector\Virtual\Documents;

/**
 * Devices factory
 */
interface DevicesFactory
{

	public function create(Documents\Connectors\Connector $connector): Devices;

}
