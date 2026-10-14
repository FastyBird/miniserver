<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\API;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents as Zigbee2MqttDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use function str_replace;

/**
 * MQTT topic builder
 */
final class MqttBuilder
{

	public const string BRIDGE_TOPIC = '%s/#';

	private const string MQTT_TOPIC_DELIMITER = '/';

	/**
	 * Replace placeholders
	 */
	private const string BASE_TOPIC_REPLACE_STRING = '{BASE_TOPIC}';

	private const string DEVICE_REPLACE_STRING = '{DEVICE_ID}';

	private const string PROPERTY_REPLACE_STRING = '{PROPERTY_ID}';

	/**
	 * Exchange topics
	 */
	private const string DEVICE_PROPERTY_TOPIC
		= self::BASE_TOPIC_REPLACE_STRING
		. self::MQTT_TOPIC_DELIMITER
		. self::DEVICE_REPLACE_STRING
		. self::MQTT_TOPIC_DELIMITER
		. self::PROPERTY_REPLACE_STRING
		. self::MQTT_TOPIC_DELIMITER
		. 'set';

	public static function buildDevicePropertyTopic(
		string $baseTopic,
		Zigbee2MqttDocuments\Devices\Device $device,
		DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic $property,
	): string
	{
		$topic = self::DEVICE_PROPERTY_TOPIC;
		$topic = str_replace(self::BASE_TOPIC_REPLACE_STRING, $baseTopic, $topic);
		$topic = str_replace(self::DEVICE_REPLACE_STRING, $device->getIdentifier(), $topic);
		$topic = str_replace(self::PROPERTY_REPLACE_STRING, $property->getIdentifier(), $topic);

		return $topic;
	}

}
