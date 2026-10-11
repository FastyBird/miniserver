<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents as CoreDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Viera::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Viera::TYPE)]
class Viera extends HomeKitDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Viera::TYPE;
	}

}
