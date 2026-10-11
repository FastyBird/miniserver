<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Hydrators\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use Override;

/**
 * Device property condition entity hydrator
 *
 * @extends PropertyCondition<Entities\Conditions\DevicePropertyCondition>
 */
final class DevicePropertyCondition extends PropertyCondition
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'device',
		'property',
		'operator',
		'operand',
		'enabled',
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Conditions\DevicePropertyCondition::class;
	}

}
