<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Hydrators\Devices;

use FastyBird\Connector\NsPanel\Entities;
use Override;

/**
 * NS Panel gateway device entity hydrator
 *
 * @extends Device<Entities\Devices\Gateway>
 */
final class Gateway extends Device
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Devices\Gateway::class;
	}

}
