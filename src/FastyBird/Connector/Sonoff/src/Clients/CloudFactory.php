<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Types;
use Override;

/**
 * Cloud client factory
 */
interface CloudFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::CLOUD;

	#[Override]
	public function create(
		Documents\Connectors\Connector $connector,
		bool $autoMode = false,
	): Cloud;

}
