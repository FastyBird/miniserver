<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Connectors;

use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Generic connector entity hydrator
 *
 * @extends Connector<Entities\Connectors\Generic>
 */
final class Generic extends Connector
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Connectors\Generic::class;
	}

}
