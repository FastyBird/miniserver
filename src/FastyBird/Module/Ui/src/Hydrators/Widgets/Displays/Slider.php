<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Slider widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\Slider>
 */
final class Slider extends Display
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'minimum_value' => 'minimumValue',
		'maximum_value' => 'maximumValue',
		'step_value' => 'stepValue',
		'precision' => 'precision',
	];

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\Slider::class;
	}

}
