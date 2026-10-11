<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Documents\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Devices\SubDevice::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Devices\SubDevice::TYPE)]
final class SubDevice extends Device
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Devices\SubDevice::TYPE;
	}

}
