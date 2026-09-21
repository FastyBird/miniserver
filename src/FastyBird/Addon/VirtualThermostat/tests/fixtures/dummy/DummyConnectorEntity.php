<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Module\Devices\Entities as DevicesEntities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class DummyConnectorEntity extends DevicesEntities\Connectors\Connector
{

	public const TYPE = 'dummy';

	public static function getType(): string
	{
		return self::TYPE;
	}

}
