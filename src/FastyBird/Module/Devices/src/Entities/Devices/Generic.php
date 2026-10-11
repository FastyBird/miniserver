<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Generic extends Entities\Devices\Device
{

	public const string TYPE = 'generic';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Module
	{
		return Sources\Module::DEVICES;
	}

}
