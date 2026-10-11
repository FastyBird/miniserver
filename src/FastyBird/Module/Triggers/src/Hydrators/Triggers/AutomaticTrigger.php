<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Hydrators\Triggers;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Schemas;

/**
 * Automatic trigger entity hydrator
 *
 * @extends Trigger<Entities\Triggers\Automatic>
 */
final class AutomaticTrigger extends Trigger
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Triggers\Automatic::RELATIONSHIPS_CONDITIONS,
		Schemas\Triggers\Automatic::RELATIONSHIPS_ACTIONS,
		Schemas\Triggers\Automatic::RELATIONSHIPS_NOTIFICATIONS,
	];

	public function getEntityName(): string
	{
		return Entities\Triggers\Automatic::class;
	}

}
