<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel fault capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Fault>
 */
final class Fault extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Fault::class;
	}

}
