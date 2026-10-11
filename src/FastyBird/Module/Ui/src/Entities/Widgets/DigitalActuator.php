<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Module\Ui\Entities;
use Override;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class DigitalActuator extends Actuator
{

	public const string TYPE = 'digital-actuator';

	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getAllowedDisplayTypes(): array
	{
		return [
			Entities\Widgets\Displays\Button::class,
		];
	}

}
