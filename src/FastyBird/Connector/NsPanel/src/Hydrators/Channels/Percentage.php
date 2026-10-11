<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel percentage capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Percentage>
 */
final class Percentage extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Percentage::class;
	}

}
