<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel thermostat target set point capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\ThermostatTargetSetPoint>
 */
final class ThermostatTargetSetPoint extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\ThermostatTargetSetPoint::class;
	}

}
