<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients;

use FastyBird\Connector\Modbus\Documents;
use FastyBird\Connector\Modbus\Types;
use Override;

/**
 * Modbus TCP devices client factory
 */
interface TcpFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::TCP;

	#[Override]
	public function create(Documents\Connectors\Connector $connector): Tcp;

}
