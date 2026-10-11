<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Writers;

use FastyBird\Connector\Shelly\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
