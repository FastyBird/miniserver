<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Servers;

use FastyBird\Connector\HomeKit\Documents;
use Override;

/**
 * HTTP connector communication server factory
 */
interface HttpFactory extends ServerFactory
{

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Http;

}
