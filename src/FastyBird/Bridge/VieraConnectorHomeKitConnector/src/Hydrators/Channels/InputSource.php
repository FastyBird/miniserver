<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use Override;

/**
 * Input button type channel entity hydrator
 *
 * @extends Viera<Entities\Channels\InputSource>
 */
final class InputSource extends Viera
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\InputSource::class;
	}

}
