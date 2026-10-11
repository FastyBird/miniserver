<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Input button type channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\InputButton>
 */
class InputButton extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\InputButton::class;
	}

}
