<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\TelevisionSpeaker::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\TelevisionSpeaker::TYPE)]
final class TelevisionSpeaker extends Viera
{

	public static function getType(): string
	{
		return Entities\Channels\TelevisionSpeaker::TYPE;
	}

}
