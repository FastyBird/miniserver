<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Schemas\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * Zigbee2MQTT bridge entity schema
 *
 * @extends  Device<Entities\Devices\Bridge>
 */
final class Bridge extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::ZIGBEE2MQTT->value . '/device/' . Entities\Devices\Bridge::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Bridge::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
