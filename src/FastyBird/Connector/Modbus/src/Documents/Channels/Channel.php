<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Documents\Channels;

use FastyBird\Connector\Modbus\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Channel::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Channel::TYPE)]
class Channel extends DevicesDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Channel::TYPE;
	}

}
