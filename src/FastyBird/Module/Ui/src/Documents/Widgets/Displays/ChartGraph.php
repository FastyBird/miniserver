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

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Displays\ChartGraph::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\ChartGraph::TYPE)]
final class ChartGraph extends UiDocuments\Widgets\Displays\Display
{

	/**
	 * @param int<0, max>|null $precision
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		#[Configurator\MapFromKey('minimum_value')]
		private readonly float|null $minimumValue,
		#[Configurator\MapFromKey('maximum_value')]
		private readonly float|null $maximumValue,
		#[Configurator\MapFromKey('step_value')]
		private readonly float|null $stepValue,
		private readonly int|null $precision,
		#[CoreDocuments\Mapping\MapBoolLike]
		#[Configurator\MapFromKey('enable_min_max')]
		private readonly bool $enableMinMax = false,
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
		return Entities\Widgets\Displays\ChartGraph::TYPE;
	}

	public function getMinimumValue(): float|null
	{
		return $this->minimumValue;
	}

	public function getMaximumValue(): float|null
	{
		return $this->maximumValue;
	}

	public function getStepValue(): float|null
	{
		return $this->stepValue;
	}

	public function getPrecision(): int|null
	{
		return $this->precision;
	}

	public function isEnabledMinMax(): bool
	{
		return $this->enableMinMax;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'minimum_value' => $this->getMinimumValue(),
			'maximum_value' => $this->getMaximumValue(),
			'step_value' => $this->getStepValue(),
			'enable_min_max' => $this->isEnabledMinMax(),
			'precision' => $this->getPrecision(),
		]);
	}

}
