<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel startup capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Startup>
 */
final class Startup extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Startup::class;
	}

}
