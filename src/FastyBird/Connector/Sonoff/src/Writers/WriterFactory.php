<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Writers;

use FastyBird\Connector\Sonoff\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
