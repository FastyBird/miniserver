<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Module\Ui\Entities;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class AnalogActuator extends Actuator
{

	public const string TYPE = 'analog-actuator';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getAllowedDisplayTypes(): array
	{
		return [
			Entities\Widgets\Displays\Slider::class,
		];
	}

}
