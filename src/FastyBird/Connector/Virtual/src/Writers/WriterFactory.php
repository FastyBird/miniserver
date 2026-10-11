<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Writers;

use FastyBird\Connector\Virtual\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
