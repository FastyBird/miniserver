<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel percentage capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Percentage>
 */
final class Percentage extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Percentage::class;
	}

}
