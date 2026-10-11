<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Documents\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Core\Documents as CoreDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\State::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\State::TYPE)]
class State extends VirtualDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\State::TYPE;
	}

}
