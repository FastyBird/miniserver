<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Clients;

use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Types;
use Override;

/**
 * FastyBird MQTT v1 client factory
 */
interface FbMqttV1Factory extends ClientFactory
{

	public const Types\ProtocolVersion VERSION = Types\ProtocolVersion::VERSION_1;

	#[Override]
	public function create(Documents\Connectors\Connector $connector): FbMqttV1;

}
