<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: DummyDeviceEntity::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: DummyDeviceEntity::TYPE)]
class DummyDeviceDocument extends DevicesDocuments\Devices\Device
{

	public static function getType(): string
	{
		return DummyDeviceEntity::TYPE;
	}

}
