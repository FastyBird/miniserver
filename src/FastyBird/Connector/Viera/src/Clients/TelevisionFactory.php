<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Clients;

use FastyBird\Connector\Viera\Documents;
use Override;

/**
 * Lan devices client factory
 */
interface TelevisionFactory extends ClientFactory
{

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Television;

}
