<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Writers;

use FastyBird\Connector\Modbus\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
