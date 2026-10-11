<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Devices;

use FastyBird\Connector\NsPanel\Entities;

/**
 * NS Panel gateway device entity hydrator
 *
 * @extends Device<Entities\Devices\Gateway>
 */
final class Gateway extends Device
{

	public function getEntityName(): string
	{
		return Entities\Devices\Gateway::class;
	}

}
