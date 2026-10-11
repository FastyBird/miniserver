<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;

/**
 * Input button type channel entity hydrator
 *
 * @extends Viera<Entities\Channels\Television>
 */
class Television extends Viera
{

	public function getEntityName(): string
	{
		return Entities\Channels\Television::class;
	}

}
