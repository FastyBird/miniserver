<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\InputSwitch::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\InputSwitch::TYPE)]
class InputSwitch extends Shelly
{

	public static function getType(): string
	{
		return Entities\Channels\InputSwitch::TYPE;
	}

}
