<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use Override;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class TelevisionSpeaker extends Viera
{

	public const string TYPE = 'viera-connector-homekit-connector-bridge-television-speaker';

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

}
