<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Hydrators as HomeKitHydrators;
use Override;

/**
 * Thermostat channel entity hydrator
 *
 * @extends HomeKitHydrators\Channels\Channel<Entities\Channels\Thermostat>
 */
final class Thermostat extends HomeKitHydrators\Channels\Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Thermostat::class;
	}

}
