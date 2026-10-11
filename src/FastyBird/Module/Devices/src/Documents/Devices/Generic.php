<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Documents\Devices;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Generic::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Generic::TYPE)]
final class Generic extends DevicesDocuments\Devices\Device
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Devices\Generic::TYPE;
	}

}
