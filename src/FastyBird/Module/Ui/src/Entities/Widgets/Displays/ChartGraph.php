<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Ui\Entities;
use Override;
use function array_merge;
use function ctype_digit;
use function is_int;
use function is_string;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class ChartGraph extends Display implements Entities\Widgets\Displays\Parameters\MinimumValue,
	Entities\Widgets\Displays\Parameters\MaximumValue,
	Entities\Widgets\Displays\Parameters\StepValue,
	Entities\Widgets\Displays\Parameters\Precision
{

	use Entities\Widgets\Displays\Parameters\HasMinimumValue;
	use Entities\Widgets\Displays\Parameters\HasMaximumValue;
	use Entities\Widgets\Displays\Parameters\HasStepValue;
	use Entities\Widgets\Displays\Parameters\HasPrecision;

	public const string TYPE = 'chart-graph';

	// What the chart shows when it is opened, in seconds back from now: a day
	public const int DEFAULT_PERIOD = 86_400;

	// The shortest and the longest period the history can be asked for: a minute, and 366 days
	public const int MIN_PERIOD = 60;

	public const int MAX_PERIOD = 31_622_400;

	#[Attribute\Crud(writable: true)]
	protected bool $enableMinMax;

	#[Attribute\Crud(writable: true)]
	protected int|null $period = null;

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	public function setEnableMinMax(bool $state): void
	{
		$this->enableMinMax = $state;

		$this->setParam('enableMinMax', $state);
	}

	public function isEnabledMinMax(): bool
	{
		return (bool) $this->getParam('enableMinMax', false);
	}

	public function setPeriod(int|null $period): void
	{
		$this->period = $period;

		$this->setParam('period', $period);
	}

	/**
	 * The period of history the chart shows, in seconds back from now. A value out of the bounds, which the API
	 * does not accept but a database can hold, is the default
	 */
	public function getPeriod(): int
	{
		$value = $this->getParam('period', self::DEFAULT_PERIOD);

		if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
			return self::DEFAULT_PERIOD;
		}

		$period = (int) $value;

		return $period >= self::MIN_PERIOD && $period <= self::MAX_PERIOD ? $period : self::DEFAULT_PERIOD;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'minimum_value' => $this->getMinimumValue(),
			'maximum_value' => $this->getMaximumValue(),
			'step_value' => $this->getStepValue(),
			'enable_min_max' => $this->isEnabledMinMax(),
			'precision' => $this->getPrecision(),
			'period' => $this->getPeriod(),
		]);
	}

}
