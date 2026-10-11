<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Documents\Devices;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Device::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Device::TYPE)]
final class Device extends DevicesDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Device::TYPE;
	}

}
