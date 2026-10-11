<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Documents;

/**
 * Base device client factory
 */
interface ClientFactory
{

	public const string MODE_CONSTANT_NAME = 'MODE';

	public function create(Documents\Connectors\Connector $connector): Client;

}
