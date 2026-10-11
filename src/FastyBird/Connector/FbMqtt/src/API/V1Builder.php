<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\API;

use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Documents as FbMqttDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Nette;
use function str_replace;

/**
 * API v1 topic builder
 */
final class V1Builder
{

	use Nette\SmartObject;

	/**
	 * Replace placeholders
	 */
	private const string DEVICE_REPLACE_STRING = '{DEVICE_ID}';

	private const string CHANNEL_REPLACE_STRING = '{CHANNEL_ID}';

	private const string PROPERTY_REPLACE_STRING = '{PROPERTY_ID}';

	private const string CONTROL_REPLACE_STRING = '{CONTROL}';

	/**
	 * Exchange topics
	 */
	private const string DEVICE_PROPERTY_TOPIC
		= FbMqtt\Constants::MQTT_API_PREFIX
		. FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::DEVICE_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. '$property'
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::PROPERTY_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. 'set';

	private const string DEVICE_CONTROL_SET_TOPIC
		= FbMqtt\Constants::MQTT_API_PREFIX
		. FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::DEVICE_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. '$control'
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::CONTROL_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. 'set';

	private const string CHANNEL_PROPERTY_TOPIC
		= FbMqtt\Constants::MQTT_API_PREFIX
		. FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::DEVICE_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. '$channel'
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::CHANNEL_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. '$property'
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::PROPERTY_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. 'set';

	private const string CHANNEL_CONTROL_SET_TOPIC
		= FbMqtt\Constants::MQTT_API_PREFIX
		. FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::DEVICE_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. '$channel'
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::CHANNEL_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. '$control'
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. self::CONTROL_REPLACE_STRING
		. FbMqtt\Constants::MQTT_TOPIC_DELIMITER
		. 'set';

	public static function buildDevicePropertyTopic(
		FbMqttDocuments\Devices\Device $device,
		DevicesDocuments\Devices\Properties\Dynamic $property,
	): string
	{
		$topic = self::DEVICE_PROPERTY_TOPIC;
		$topic = str_replace(self::DEVICE_REPLACE_STRING, $device->getIdentifier(), $topic);

		return str_replace(self::PROPERTY_REPLACE_STRING, $property->getIdentifier(), $topic);
	}

	public static function buildDeviceCommandTopic(
		FbMqttDocuments\Devices\Device $device,
		DevicesDocuments\Devices\Controls\Control $command,
	): string
	{
		$topic = self::DEVICE_CONTROL_SET_TOPIC;
		$topic = str_replace(self::DEVICE_REPLACE_STRING, $device->getIdentifier(), $topic);

		return str_replace(self::CONTROL_REPLACE_STRING, $command->getName(), $topic);
	}

	public static function buildChannelPropertyTopic(
		FbMqttDocuments\Devices\Device $device,
		FbMqttDocuments\Channels\Channel $channel,
		DevicesDocuments\Channels\Properties\Dynamic $property,
	): string
	{
		$topic = self::CHANNEL_PROPERTY_TOPIC;
		$topic = str_replace(self::DEVICE_REPLACE_STRING, $device->getIdentifier(), $topic);
		$topic = str_replace(self::CHANNEL_REPLACE_STRING, $channel->getIdentifier(), $topic);

		return str_replace(self::PROPERTY_REPLACE_STRING, $property->getIdentifier(), $topic);
	}

	public static function buildChannelCommandTopic(
		FbMqttDocuments\Devices\Device $device,
		FbMqttDocuments\Channels\Channel $channel,
		DevicesDocuments\Channels\Controls\Control $command,
	): string
	{
		$topic = self::CHANNEL_CONTROL_SET_TOPIC;
		$topic = str_replace(self::DEVICE_REPLACE_STRING, $device->getIdentifier(), $topic);
		$topic = str_replace(self::CHANNEL_REPLACE_STRING, $channel->getIdentifier(), $topic);

		return str_replace(self::CONTROL_REPLACE_STRING, $command->getName(), $topic);
	}

}
