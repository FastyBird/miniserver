<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Television extends Viera
{

	public const string TYPE = 'viera-connector-homekit-connector-bridge-television';

	public static function getType(): string
	{
		return self::TYPE;
	}

}
