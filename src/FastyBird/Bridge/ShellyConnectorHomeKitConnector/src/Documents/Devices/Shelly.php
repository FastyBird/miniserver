<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents as CoreDocuments;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Shelly::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Shelly::TYPE)]
final class Shelly extends HomeKitDocuments\Devices\Device
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Devices\Shelly::TYPE;
	}

}
