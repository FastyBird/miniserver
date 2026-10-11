<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel color temperature capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\ColorTemperature>
 */
final class ColorTemperature extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\ColorTemperature::class;
	}

}
