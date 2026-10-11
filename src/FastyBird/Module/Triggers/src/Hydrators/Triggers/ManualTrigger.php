<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Triggers;

use FastyBird\Module\Triggers\Entities;

/**
 * Manual trigger entity hydrator
 *
 * @extends Trigger<Entities\Triggers\Manual>
 */
final class ManualTrigger extends Trigger
{

	public function getEntityName(): string
	{
		return Entities\Triggers\Manual::class;
	}

}
