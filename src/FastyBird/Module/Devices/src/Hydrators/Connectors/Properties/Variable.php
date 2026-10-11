<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Hydrators\Connectors\Properties;

use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Connector property entity hydrator
 *
 * @extends Property<Entities\Connectors\Properties\Variable>
 */
final class Variable extends Property
{

	/**
	 * @return class-string<Entities\Connectors\Properties\Variable>
	 */
	#[Override]
	public function getEntityName(): string
	{
		return Entities\Connectors\Properties\Variable::class;
	}

}
