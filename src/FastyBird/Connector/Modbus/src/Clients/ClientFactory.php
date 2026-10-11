<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Clients;

use FastyBird\Connector\Modbus\Documents;

/**
 * Base client factory
 */
interface ClientFactory
{

	public const string MODE_CONSTANT_NAME = 'MODE';

	public function create(Documents\Connectors\Connector $connector): Client;

}
