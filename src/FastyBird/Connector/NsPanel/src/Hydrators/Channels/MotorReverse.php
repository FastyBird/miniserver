<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel motor reverse capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\MotorReverse>
 */
final class MotorReverse extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\MotorReverse::class;
	}

}
