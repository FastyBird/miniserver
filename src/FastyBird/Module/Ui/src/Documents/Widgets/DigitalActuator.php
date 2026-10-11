<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\DigitalActuator::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\DigitalActuator::TYPE)]
final class DigitalActuator extends UiDocuments\Widgets\Widget
{

	public static function getType(): string
	{
		return Entities\Widgets\DigitalActuator::TYPE;
	}

}
