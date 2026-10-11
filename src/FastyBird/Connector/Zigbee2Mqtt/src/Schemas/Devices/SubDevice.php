<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Schemas\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * Zigbee2MQTT sub-device entity schema
 *
 * @extends  Device<Entities\Devices\SubDevice>
 */
final class SubDevice extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::ZIGBEE2MQTT->value . '/device/' . Entities\Devices\SubDevice::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\SubDevice::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
