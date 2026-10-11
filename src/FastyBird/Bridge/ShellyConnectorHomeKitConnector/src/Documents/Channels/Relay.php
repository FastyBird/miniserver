<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Relay::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Relay::TYPE)]
final class Relay extends Shelly
{

	public static function getType(): string
	{
		return Entities\Channels\Relay::TYPE;
	}

}
