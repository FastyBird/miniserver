<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Hydrators\Devices;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Hydrators as VirtualHydrators;

/**
 * Virtual thermostat device entity hydrator
 *
 * @extends VirtualHydrators\Devices\Device<Entities\Devices\Device>
 */
final class Device extends VirtualHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\Devices\Device::class;
	}

}
