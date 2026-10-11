<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Documents\Devices;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Device::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Device::TYPE)]
final class Device extends DevicesDocuments\Devices\Device
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Devices\Device::TYPE;
	}

}
