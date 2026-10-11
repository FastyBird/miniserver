<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;

/**
 * Input button type channel entity hydrator
 *
 * @extends Viera<Entities\Channels\InputSource>
 */
class InputSource extends Viera
{

	public function getEntityName(): string
	{
		return Entities\Channels\InputSource::class;
	}

}
