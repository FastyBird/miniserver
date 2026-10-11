<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\Television::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Television::TYPE)]
final class Television extends Viera
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Television::TYPE;
	}

}
