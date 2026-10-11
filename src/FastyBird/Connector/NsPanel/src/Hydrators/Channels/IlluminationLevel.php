<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel illumination level capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\IlluminationLevel>
 */
final class IlluminationLevel extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\IlluminationLevel::class;
	}

}
