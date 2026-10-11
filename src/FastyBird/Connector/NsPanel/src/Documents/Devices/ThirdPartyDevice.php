<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Devices\ThirdPartyDevice::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Devices\ThirdPartyDevice::TYPE)]
final class ThirdPartyDevice extends Device
{

	public static function getType(): string
	{
		return Entities\Devices\ThirdPartyDevice::TYPE;
	}

}
