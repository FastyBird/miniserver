<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Documents\Channels;

use FastyBird\Connector\Viera\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Channels\Channel::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Channel::TYPE)]
final class Channel extends DevicesDocuments\Channels\Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\Channel::TYPE;
	}

}
