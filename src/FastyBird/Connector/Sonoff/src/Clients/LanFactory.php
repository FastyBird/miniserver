<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Types;
use Override;

/**
 * Lan devices client factory
 */
interface LanFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::LAN;

	#[Override]
	public function create(Documents\Connectors\Connector $connector, bool $autoMode = false): Lan;

}
