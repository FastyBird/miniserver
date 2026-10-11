<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use Override;

/**
 * Input button type channel entity hydrator
 *
 * @extends Viera<Entities\Channels\Television>
 */
final class Television extends Viera
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\Television::class;
	}

}
