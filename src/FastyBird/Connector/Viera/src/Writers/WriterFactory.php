<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Writers;

use FastyBird\Connector\Viera\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
