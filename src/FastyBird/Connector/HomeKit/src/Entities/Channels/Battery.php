<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Battery extends Channel
{

	public const string TYPE = 'homekit-connector-battery';

	public static function getType(): string
	{
		return self::TYPE;
	}

}
