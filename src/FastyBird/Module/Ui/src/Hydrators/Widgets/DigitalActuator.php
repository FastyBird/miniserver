<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use FastyBird\Module\Ui\Entities;

/**
 * Digital actuator widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\DigitalActuator>
 */
final class DigitalActuator extends Widget
{

	public function getEntityName(): string
	{
		return Entities\Widgets\DigitalActuator::class;
	}

}
