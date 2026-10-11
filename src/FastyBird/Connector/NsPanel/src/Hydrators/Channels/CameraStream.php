<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel camera stream capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\CameraStream>
 */
final class CameraStream extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\CameraStream::class;
	}

}
