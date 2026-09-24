<?php declare(strict_types = 1);

/**
 * ChartGraph.php
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
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Ui\Documents as UiDocuments;
use FastyBird\Module\Ui\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

#[CoreDocuments\Mapping\Document(entity: Entities\Widgets\Displays\ChartGraph::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Widgets\Displays\ChartGraph::TYPE)]
class ChartGraph extends UiDocuments\Widgets\Displays\Display
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $widget,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('minimum_value')]
		private readonly float|null $minimumValue,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('maximum_value')]
		private readonly float|null $maximumValue,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('step_value')]
		private readonly float|null $stepValue,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(min: 0),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $precision,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(castBoolLike: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('enable_min_max')]
		private readonly bool $enableMinMax = false,
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id, $widget, $owner, $createdAt, $updatedAt);
	}

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
