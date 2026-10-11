<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Types;
use Override;

/**
 * Local devices client factory
 */
interface LocalFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::LOCAL;

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Local;

}
