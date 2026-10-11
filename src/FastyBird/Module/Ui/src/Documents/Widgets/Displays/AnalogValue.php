<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Orisai\ObjectMapper;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Displays\AnalogValue::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\AnalogValue::TYPE)]
final class AnalogValue extends UiDocuments\Widgets\Displays\Display
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $precision,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $widget, $owner, $createdAt, $updatedAt);
	}

	public static function getType(): string
	{
		return Entities\Widgets\Displays\AnalogValue::TYPE;
	}

	public function getPrecision(): int|null
	{
		return $this->precision;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'precision' => $this->getPrecision(),
		]);
	}

}
