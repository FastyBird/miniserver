<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Types;

/**
 * Local devices client factory
 */
interface LocalFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::LOCAL;

	public function create(Documents\Connectors\Connector $connector): Local;

}
