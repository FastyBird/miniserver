<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel motor control capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\MotorControl>
 */
final class MotorControl extends Channel
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\MotorControl::class;
	}

}
