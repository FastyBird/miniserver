<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel power capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Power>
 */
final class Power extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Power::class;
	}

}
