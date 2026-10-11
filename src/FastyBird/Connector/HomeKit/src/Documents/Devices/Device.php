<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Documents\Devices;

use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Device::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Device::TYPE)]
class Device extends DevicesDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Device::TYPE;
	}

}
