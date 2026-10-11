<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Documents\Connectors;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Connectors\Connector::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Connectors\Connector::TYPE)]
class Connector extends DevicesDocuments\Connectors\Connector
{

	public static function getType(): string
	{
		return Entities\Connectors\Connector::TYPE;
	}

}
