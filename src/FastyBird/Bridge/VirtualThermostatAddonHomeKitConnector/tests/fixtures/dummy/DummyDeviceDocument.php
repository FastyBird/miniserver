<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Documents;

#[ApplicationDocuments\Mapping\Document(entity: DummyDeviceEntity::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: DummyDeviceEntity::TYPE)]
class DummyDeviceDocument extends Documents\Devices\Device
{

	public static function getType(): string
	{
		return DummyDeviceEntity::TYPE;
	}

}
