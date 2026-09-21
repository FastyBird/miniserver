<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[ApplicationDocuments\Mapping\Document(entity: DummyConnectorEntity::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: DummyConnectorEntity::TYPE)]
class DummyConnectorDocument extends DevicesDocuments\Connectors\Connector
{

	public static function getType(): string
	{
		return DummyConnectorEntity::TYPE;
	}

}
