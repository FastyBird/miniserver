<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel thermostat mode detect capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\ThermostatModeDetect>
 */
final class ThermostatModeDetect extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\ThermostatModeDetect::class;
	}

}
