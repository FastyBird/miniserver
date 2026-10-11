<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Writers;

use FastyBird\Connector\FbMqtt\Documents;

/**
 * Device state writer interface factory
 */
interface WriterFactory
{

	public function create(Documents\Connectors\Connector $connector): Writer;

}
