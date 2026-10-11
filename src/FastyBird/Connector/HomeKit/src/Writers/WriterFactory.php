<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Writers;

use FastyBird\Connector\HomeKit\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
