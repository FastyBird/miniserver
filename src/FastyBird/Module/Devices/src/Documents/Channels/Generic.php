<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Channels;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Generic::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Generic::TYPE)]
class Generic extends DevicesDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Generic::TYPE;
	}

}
