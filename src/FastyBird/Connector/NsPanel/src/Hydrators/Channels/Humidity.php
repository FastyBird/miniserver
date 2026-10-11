<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel humidity capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Humidity>
 */
final class Humidity extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Humidity::class;
	}

}
