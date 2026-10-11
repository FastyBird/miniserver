<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Module\Ui\Entities;

/**
 * Gauge widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\Gauge>
 */
final class Gauge extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'precision' => 'precision',
	];

	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\Gauge::class;
	}

}
