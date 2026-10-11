<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Analog actuator widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\AnalogActuator>
 */
final class AnalogActuator extends Widget
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\AnalogActuator::class;
	}

}
