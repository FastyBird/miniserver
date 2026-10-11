<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Connectors\Properties;

use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Hydrators;
use FastyBird\Module\Devices\Schemas;

/**
 * Connector property entity hydrator
 *
 * @template T of Entities\Connectors\Properties\Property
 * @extends  Hydrators\Property<T>
 */
abstract class Property extends Hydrators\Property
{

	/** @var array<string> */
	protected array $relationships
		= [
			Schemas\Connectors\Properties\Property::RELATIONSHIPS_CONNECTOR,
		];

}
