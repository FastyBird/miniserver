<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel thermostat capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Thermostat>
 */
final class Thermostat extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Thermostat::class;
	}

}
