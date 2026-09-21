<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[ApplicationDocuments\Mapping\Document(entity: DummyDeviceEntity::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: DummyDeviceEntity::TYPE)]
class DummyDeviceDocument extends DevicesDocuments\Devices\Device
{

	public static function getType(): string
	{
		return DummyDeviceEntity::TYPE;
	}

}
