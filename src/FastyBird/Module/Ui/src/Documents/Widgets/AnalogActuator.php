<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\AnalogActuator::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\AnalogActuator::TYPE)]
final class AnalogActuator extends UiDocuments\Widgets\Widget
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Widgets\AnalogActuator::TYPE;
	}

}
