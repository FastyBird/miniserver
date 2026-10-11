<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel color RGB capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\ColorRgb>
 */
final class ColorRgb extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\ColorRgb::class;
	}

}
