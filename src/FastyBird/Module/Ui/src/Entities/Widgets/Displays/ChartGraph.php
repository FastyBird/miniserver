<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Persistence\Mapping\Attribute;
use FastyBird\Module\Ui\Entities;
use Override;
use function array_merge;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class ChartGraph extends Display implements Entities\Widgets\Displays\Parameters\MinimumValue,
	Entities\Widgets\Displays\Parameters\MaximumValue,
	Entities\Widgets\Displays\Parameters\StepValue,
	Entities\Widgets\Displays\Parameters\Precision
{

	use Entities\Widgets\Displays\Parameters\TMinimumValue;
	use Entities\Widgets\Displays\Parameters\TMaximumValue;
	use Entities\Widgets\Displays\Parameters\TStepValue;
	use Entities\Widgets\Displays\Parameters\TPrecision;

	public const string TYPE = 'chart-graph';

	#[Attribute\Crud(writable: true)]
	protected bool $enableMinMax;

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
		]);
	}

}
