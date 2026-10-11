<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel detect capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Detect>
 */
final class Detect extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Detect::class;
	}

}
