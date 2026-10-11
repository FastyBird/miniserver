<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel motor reverse capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\MotorReverse>
 */
final class MotorReverse extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\MotorReverse::class;
	}

}
