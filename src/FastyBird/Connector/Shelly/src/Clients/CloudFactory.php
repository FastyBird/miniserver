<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Types;
use Override;

/**
 * Cloud client factory
 */
interface CloudFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::CLOUD;

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Cloud;

}
