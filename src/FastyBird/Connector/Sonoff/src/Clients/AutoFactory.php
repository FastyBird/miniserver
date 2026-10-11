<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Types;
use Override;

/**
 * Automatic selection devices client factory
 */
interface AutoFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::AUTO;

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Auto;

}
