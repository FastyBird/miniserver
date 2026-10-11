<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel illumination level capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\IlluminationLevel>
 */
final class IlluminationLevel extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\IlluminationLevel::class;
	}

}
