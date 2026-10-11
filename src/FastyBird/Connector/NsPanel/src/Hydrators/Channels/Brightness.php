<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel brightness capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Brightness>
 */
final class Brightness extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Brightness::class;
	}

}
