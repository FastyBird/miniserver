<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Hydrators\Devices;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;
use Override;

/**
 * FastyBird MQTT device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\Devices\Device>
 */
final class Device extends DevicesHydrators\Devices\Device
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

}
