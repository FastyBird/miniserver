<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Documents;

/**
 * Base client factory
 */
interface ClientFactory
{

	public const string MODE_CONSTANT_NAME = 'MODE';

	public function create(Documents\Connectors\Connector $connector): Client;

}
