<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Hydrators\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;

/**
 * Zigbee2MQTT bridge device entity hydrator
 *
 * @extends Device<Entities\Devices\Bridge>
 */
final class Bridge extends Device
{

	public function getEntityName(): string
	{
		return Entities\Devices\Bridge::class;
	}

}
