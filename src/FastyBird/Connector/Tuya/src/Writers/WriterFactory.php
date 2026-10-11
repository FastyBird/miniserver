<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Writers;

use FastyBird\Connector\Tuya\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
