<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Documents\Widgets\DataSources;

use DateTimeInterface;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Persistence\Rules;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Ui\Documents as UiDocuments;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\MappedSuperclass]
abstract class Property extends UiDocuments\Widgets\DataSources\DataSource
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		#[Rules\UuidValue()]
		private readonly Uuid\UuidInterface $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Button::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Switcher::class),
			new ObjectMapper\Rules\BackedEnumValue(class: Payloads\Cover::class),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value = null,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $widget, $owner, $createdAt, $updatedAt);
	}

	public static function getType(): string
	{
		return Entities\Widgets\DataSources\ChannelProperty::TYPE;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function getValue(): Payloads\Payload|float|DateTimeInterface|bool|int|string|null
	{
		return $this->value;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'property' => $this->getProperty()->toString(),
			'value' => Utilities\Value::flattenValue($this->getValue()),
		]);
	}

}
