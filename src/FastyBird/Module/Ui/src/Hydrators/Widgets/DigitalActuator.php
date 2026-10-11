<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Digital actuator widget entity hydrator
 *
 * @extends Widget<Entities\Widgets\DigitalActuator>
 */
final class DigitalActuator extends Widget
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\DigitalActuator::class;
	}

}
