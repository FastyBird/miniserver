<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Types;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Displays\GroupedButton::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\GroupedButton::TYPE)]
final class GroupedButton extends UiDocuments\Widgets\Displays\Display
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		private readonly Types\WidgetIcon|null $icon,
		#[CoreDocuments\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
		#[Configurator\MapFromKey('created_at')]
		DateTimeInterface|null $createdAt = null,
		#[Configurator\MapFromKey('updated_at')]
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $widget, $owner, $createdAt, $updatedAt);
	}

	#[Override]
	public static function getType(): string
	{
		return Entities\Widgets\Displays\GroupedButton::TYPE;
	}

	public function getIcon(): Types\WidgetIcon|null
	{
		return $this->icon;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'icon' => $this->getIcon()?->value,
		]);
	}

}
