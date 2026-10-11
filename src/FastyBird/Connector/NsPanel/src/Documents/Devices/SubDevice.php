<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Devices\SubDevice::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Devices\SubDevice::TYPE)]
class SubDevice extends Device
{

	public static function getType(): string
	{
		return Entities\Devices\SubDevice::TYPE;
	}

}
