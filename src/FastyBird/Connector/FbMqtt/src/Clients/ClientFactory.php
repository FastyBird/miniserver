<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Clients;

use FastyBird\Connector\FbMqtt\Documents;

/**
 * Base client factory
 */
interface ClientFactory
{

	public const string VERSION_CONSTANT_NAME = 'VERSION';

	public function create(Documents\Connectors\Connector $connector): FbMqttV1;

}
