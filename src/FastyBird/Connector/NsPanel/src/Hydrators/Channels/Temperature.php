<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel temperature capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\Temperature>
 */
final class Temperature extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Temperature::class;
	}

}
