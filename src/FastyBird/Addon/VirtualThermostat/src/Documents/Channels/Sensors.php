<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Documents\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Core\Documents as CoreDocuments;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Sensors::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Sensors::TYPE)]
final class Sensors extends VirtualDocuments\Channels\Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Sensors::TYPE;
	}

}
