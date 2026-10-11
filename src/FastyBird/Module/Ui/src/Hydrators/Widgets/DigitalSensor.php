<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use FastyBird\Module\Ui\Entities;

/**
 * Digital sensor widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\DigitalSensor>
 */
final class DigitalSensor extends Widget
{

	public function getEntityName(): string
	{
		return Entities\Widgets\DigitalSensor::class;
	}

}
