<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Clients;

use FastyBird\Connector\Viera\Documents;

/**
 * Lan devices client factory
 */
interface TelevisionFactory extends ClientFactory
{

	public function create(Documents\Connectors\Connector $connector): Television;

}
