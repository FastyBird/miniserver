<?php declare(strict_types = 1);

/**
 * MqttBuilder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\API;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents as Zigbee2MqttDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Nette;
use function str_replace;

/**
 * MQTT topic builder
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MqttBuilder
{

	use Nette\SmartObject;

	/**
	 * Replace placeholders
	 */
	private const BASE_TOPIC_REPLACE_STRING = '{BASE_TOPIC}';

	private const DEVICE_REPLACE_STRING = '{DEVICE_ID}';

	private const PROPERTY_REPLACE_STRING = '{PROPERTY_ID}';

	/**
	 * Exchange topics
	 */
	private const DEVICE_PROPERTY_TOPIC
		= self::BASE_TOPIC_REPLACE_STRING
		. Zigbee2Mqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::DEVICE_REPLACE_STRING
		. Zigbee2Mqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::PROPERTY_REPLACE_STRING
		. Zigbee2Mqtt\Constants::MQTT_TOPIC_DELIMITER
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
