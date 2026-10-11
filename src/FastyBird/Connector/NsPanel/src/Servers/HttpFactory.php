<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Servers;

use FastyBird\Connector\NsPanel\Documents;
use Override;

/**
 * Connector HTTP communication server factory
 */
interface HttpFactory extends ServerFactory
{

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Http;

}
