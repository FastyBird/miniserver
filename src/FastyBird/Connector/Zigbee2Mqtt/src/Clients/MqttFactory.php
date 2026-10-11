<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Types;

/**
 * Zigbee2MQTT MQTT client factory
 */
interface MqttFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::MQTT;

	public function create(Documents\Connectors\Connector $connector): Mqtt;

}
