<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Channels;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel motor calibration capability channel entity hydrator
 *
 * @extends Channel<Entities\Channels\MotorCalibration>
 */
final class MotorCalibration extends Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\MotorCalibration::class;
	}

}
