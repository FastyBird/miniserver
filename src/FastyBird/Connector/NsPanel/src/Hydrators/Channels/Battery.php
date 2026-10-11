<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel battery capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Battery>
 */
final class Battery extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Battery::class;
	}

}
