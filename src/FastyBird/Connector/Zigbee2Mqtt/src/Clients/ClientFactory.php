<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use FastyBird\Connector\Zigbee2Mqtt\Documents;

/**
 * Base client factory
 */
interface ClientFactory
{

	public const string MODE_CONSTANT_NAME = 'MODE';

	public function create(Documents\Connectors\Connector $connector): Mqtt;

}
