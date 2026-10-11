<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Documents\Connectors;

use FastyBird\Connector\Modbus\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Connectors\Connector::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Connectors\Connector::TYPE)]
final class Connector extends DevicesDocuments\Connectors\Connector
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Connectors\Connector::TYPE;
	}

}
