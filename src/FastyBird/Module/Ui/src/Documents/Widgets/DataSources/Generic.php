<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\DataSources;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Override;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\DataSources\Generic::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\DataSources\Generic::TYPE)]
final class Generic extends UiDocuments\Widgets\DataSources\DataSource
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Widgets\DataSources\Generic::TYPE;
	}

}
