<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Analog value widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\AnalogValue>
 */
final class AnalogValue extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'precision',
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\AnalogValue::class;
	}

}
