<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use Override;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class DigitalValue extends Display
{

	public const string TYPE = 'digital-value';

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

}
