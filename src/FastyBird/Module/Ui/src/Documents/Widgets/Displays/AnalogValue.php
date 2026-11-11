<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Documents\Widgets\Displays;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Override;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Displays\AnalogValue::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\AnalogValue::TYPE)]
final class AnalogValue extends UiDocuments\Widgets\Displays\Display
{

	/**
	 * @param int<0, max>|null $precision
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		private readonly int|null $precision,
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
