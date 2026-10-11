<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Valve::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Valve::TYPE)]
final class Valve extends Shelly
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Valve::TYPE;
	}

}
