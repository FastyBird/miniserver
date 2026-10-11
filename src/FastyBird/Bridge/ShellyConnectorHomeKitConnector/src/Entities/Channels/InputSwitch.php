<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class InputSwitch extends Shelly
{

	public const string TYPE = 'shelly-connector-homekit-connector-bridge-input-switch';

	public static function getType(): string
	{
		return self::TYPE;
	}

}
