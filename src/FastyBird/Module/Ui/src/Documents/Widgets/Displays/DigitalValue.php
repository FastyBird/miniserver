<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Displays\DigitalValue::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\DigitalValue::TYPE)]
final class DigitalValue extends UiDocuments\Widgets\Displays\Display
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Widgets\Displays\DigitalValue::TYPE;
	}

}
