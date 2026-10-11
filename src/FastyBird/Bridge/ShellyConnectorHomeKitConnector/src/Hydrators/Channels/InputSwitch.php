<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Hydrators\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;

/**
 * Input switch type channel entity hydrator
 *
 * @extends Shelly<Entities\Channels\InputSwitch>
 */
final class InputSwitch extends Shelly
{

	public function getEntityName(): string
	{
		return Entities\Channels\InputSwitch::class;
	}

}
