<?php declare(strict_types = 1);

/**
 * TextType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt\Types as Zigbee2MqttTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;

/**
 * Text type expose type message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TextType extends Type
{

	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2MqttTypes\ExposeType::TEXT->value])]
		private readonly string $type,
	)
	{
		parent::__construct();
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): Zigbee2MqttTypes\ExposeType
	{
		return Zigbee2MqttTypes\ExposeType::from($this->type);
	}

	public function getDataType(): ValuesTypes\DataType
	{
		return ValuesTypes\DataType::STRING;
	}

}
