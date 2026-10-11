<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel press capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Press>
 */
final class Press extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Press::class;
	}

}
