<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\WindowCovering::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\WindowCovering::TYPE)]
final class WindowCovering extends Shelly
{

	public static function getType(): string
	{
		return Entities\Channels\WindowCovering::TYPE;
	}

}
