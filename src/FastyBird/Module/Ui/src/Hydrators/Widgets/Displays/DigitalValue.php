<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\Displays;

use FastyBird\Module\Ui\Entities;

/**
 * Digital value widget display entity hydrator
 *
 * @extends Display<Entities\Widgets\Displays\DigitalValue>
 */
final class DigitalValue extends Display
{

	public function getEntityName(): string
	{
		return Entities\Widgets\Displays\DigitalValue::class;
	}

}
