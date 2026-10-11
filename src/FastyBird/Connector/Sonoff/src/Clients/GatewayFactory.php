<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Types;
use Override;

/**
 * Gateway client factory
 */
interface GatewayFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::GATEWAY;

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Gateway;

}
