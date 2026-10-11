<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Devices\Properties;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Hydrators;
use FastyBird\Module\Devices\Schemas;

/**
 * Device property entity hydrator
 *
 * @template T of Entities\Devices\Properties\Property
 * @extends  Hydrators\Property<T>
 */
abstract class Property extends Hydrators\Property
{

	/** @var array<string> */
	protected array $relationships
		= [
			Schemas\Devices\Properties\Property::RELATIONSHIPS_DEVICE,
			Schemas\Devices\Properties\Property::RELATIONSHIPS_PARENT,
		];

}
