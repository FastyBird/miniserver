<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Types;

/**
 * Cloud devices client factory
 */
interface CloudFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::CLOUD;

	public function create(Documents\Connectors\Connector $connector): Cloud;

}
