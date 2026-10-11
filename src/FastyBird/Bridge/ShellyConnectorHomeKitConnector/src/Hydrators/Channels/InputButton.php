<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use Override;

/**
 * Input button type channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\InputButton>
 */
final class InputButton extends Shelly
{

	#[Override]
	public function getEntityName(): string
	{
		return Entities\Channels\InputButton::class;
	}

}
