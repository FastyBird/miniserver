<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;
use Override;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Generic extends Entities\Channels\Channel
{

	public const string TYPE = 'generic';

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getSource(): Sources\Module
	{
		return Sources\Module::DEVICES;
	}

}
