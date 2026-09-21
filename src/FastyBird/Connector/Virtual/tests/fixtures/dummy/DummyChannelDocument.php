<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Module\Devices\Documents;

#[ApplicationDocuments\Mapping\Document(entity: DummyChannelEntity::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: DummyChannelEntity::TYPE)]
class DummyChannelDocument extends Documents\Channels\Channel
{

	public static function getType(): string
	{
		return DummyChannelEntity::TYPE;
	}

}
