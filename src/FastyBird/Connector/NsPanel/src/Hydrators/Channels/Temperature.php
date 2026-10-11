<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel temperature capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Temperature>
 */
final class Temperature extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Temperature::class;
	}

}
