<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\InputSource::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\InputSource::TYPE)]
final class InputSource extends Viera
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\InputSource::TYPE;
	}

}
