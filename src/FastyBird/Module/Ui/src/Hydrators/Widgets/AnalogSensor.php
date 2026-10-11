<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use FastyBird\Module\Ui\Entities;

/**
 * Analog sensor widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\AnalogSensor>
 */
final class AnalogSensor extends Widget
{

	public function getEntityName(): string
	{
		return Entities\Widgets\AnalogSensor::class;
	}

}
