<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel thermostat mode detect capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\ThermostatModeDetect>
 */
final class ThermostatModeDetect extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\ThermostatModeDetect::class;
	}

}
