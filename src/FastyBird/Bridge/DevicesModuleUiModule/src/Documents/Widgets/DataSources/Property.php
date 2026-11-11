<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Documents\Widgets\DataSources;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\MappedSuperclass]
abstract class Property extends UiDocuments\Widgets\DataSources\DataSource
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		private readonly Uuid\UuidInterface $property,
		#[DevicesDocuments\Mapping\MapStateValue]
		private readonly bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value = null,
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
