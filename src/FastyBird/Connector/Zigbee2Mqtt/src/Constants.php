<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt;

/**
 * Service constants
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Zigbee2MQTT topics subscribe format
	 */
	public const string BRIDGE_TOPIC = '%s/#';

	/**
	 * MQTT topic delimiter
	 */
	public const string MQTT_TOPIC_DELIMITER = '/';

	/**
	 * MQTT message QOS values
	 */
	public const int MQTT_API_QOS_0 = 0;

	public const int MQTT_API_QOS_1 = 1;

	public const int MQTT_API_QOS_2 = 2;

	public const string VALUE_NOT_AVAILABLE = 'n/a';

	public const string CHANNEL_IDENTIFIER_REGEX = '/^(?P<type>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string CHANNEL_SPECIAL_IDENTIFIER_REGEX = '/^(?P<type>(light|switch|fan|cover|lock|climate){1})_(?P<subtype>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/';

	public const string IEEE_ADDRESS_REGEX = '/^0x([a-zA-Z0-9]+)$/';

	public const string EVENT_CONNECT = 'connect';

	public const string EVENT_DISCONNECT = 'disconnect';

	public const string EVENT_CLOSE = 'close';

	public const string EVENT_WARNING = 'warning';

	public const string EVENT_ERROR = 'error';

	public const string EVENT_MESSAGE = 'message';

	public const string EVENT_PUBLISH = 'publish';

	public const string EVENT_SUBSCRIBE = 'subscribe';

	public const string EVENT_UNSUBSCRIBE = 'unsubscribe';

}
