<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Hydrators\Widgets\DataSources;

use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Generic data source entity hydrator
 *
 * @extends DataSource<Entities\Widgets\DataSources\Generic>
 */
final class Generic extends DataSource
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Widgets\DataSources\Generic::class;
	}

}
