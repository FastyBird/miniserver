<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients;

use FastyBird\Connector\Modbus\Documents;
use FastyBird\Connector\Modbus\Types;

/**
 * Modbus RTU devices client factory
 */
interface RtuFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::RTU;

	public function create(Documents\Connectors\Connector $connector): Rtu;

}
