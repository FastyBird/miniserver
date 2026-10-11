<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Hydrators\Actions;

use FastyBird\Automator\DevicesModule\Entities;

/**
 * Device property action entity hydrator
 *
 * @extends PropertyAction<Entities\Actions\DevicePropertyAction>
 */
final class DevicePropertyAction extends PropertyAction
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'device',
		'property',
		'value',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Actions\DevicePropertyAction::class;
	}

}
