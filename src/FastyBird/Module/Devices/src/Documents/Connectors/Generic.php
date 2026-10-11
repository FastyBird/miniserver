<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Connectors;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;

#[CoreDocuments\Mapping\Document(entity: Entities\Connectors\Generic::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Connectors\Generic::TYPE)]
class Generic extends DevicesDocuments\Connectors\Connector
{

	public static function getType(): string
	{
		return Entities\Connectors\Generic::TYPE;
	}

}
