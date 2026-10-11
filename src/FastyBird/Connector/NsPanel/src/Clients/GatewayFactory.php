<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Types;

/**
 * Connector gateway client factory
 */
interface GatewayFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::GATEWAY;

	public function create(Documents\Connectors\Connector $connector): Gateway;

}
