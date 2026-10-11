<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel toggle capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Toggle>
 */
final class Toggle extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Toggle::class;
	}

}
