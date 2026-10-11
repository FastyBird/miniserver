<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Devices\Gateway::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Devices\Gateway::TYPE)]
class Gateway extends Device
{

	public static function getType(): string
	{
		return Entities\Devices\Gateway::TYPE;
	}

}
