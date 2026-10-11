<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Types;

/**
 * MQTT client factory
 */
interface MqttFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::MQTT;

	public function create(Documents\Connectors\Connector $connector): Mqtt;

}
