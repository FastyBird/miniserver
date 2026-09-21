<?php declare(strict_types = 1);

/**
 * GroupedButton.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use DateTimeInterface;
use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Widgets\Displays\GroupedButton::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\GroupedButton::TYPE)]
class GroupedButton extends Documents\Widgets\Displays\Display
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\WidgetIcon::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\WidgetIcon::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly Types\WidgetIcon|null $icon,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $widget, $owner, $createdAt, $updatedAt);
	}

	public static function getType(): string
	{
		return Entities\Widgets\Displays\GroupedButton::TYPE;
	}

	public function getIcon(): Types\WidgetIcon|null
	{
		return $this->icon;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'icon' => $this->getIcon()?->value,
		]);
	}

}
