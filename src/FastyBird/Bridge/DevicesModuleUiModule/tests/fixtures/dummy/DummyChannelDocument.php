<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: DummyChannelEntity::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: DummyChannelEntity::TYPE)]
class DummyChannelDocument extends DevicesDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return DummyChannelEntity::TYPE;
	}

}
