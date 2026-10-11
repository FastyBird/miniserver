<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Lightbulb::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Lightbulb::TYPE)]
final class Lightbulb extends Shelly
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Lightbulb::TYPE;
	}

}
