<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Module\Ui\Entities;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Slider extends Display implements Entities\Widgets\Displays\Parameters\MinimumValue,
	Entities\Widgets\Displays\Parameters\MaximumValue,
	Entities\Widgets\Displays\Parameters\StepValue,
	Entities\Widgets\Displays\Parameters\Precision
{

	use Entities\Widgets\Displays\Parameters\HasMinimumValue;
	use Entities\Widgets\Displays\Parameters\HasMaximumValue;
	use Entities\Widgets\Displays\Parameters\HasStepValue;
	use Entities\Widgets\Displays\Parameters\HasPrecision;

	public const string TYPE = 'slider';

	public function __construct(
		Entities\Widgets\Widget $widget,
		float $minimumValue,
		float $maximumValue,
		float $stepValue,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($widget, $id);

		$this->setMinimumValue($minimumValue);
		$this->setMaximumValue($maximumValue);
		$this->setStepValue($stepValue);
	}

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
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
			'precision' => $this->getPrecision(),
		]);
	}

}
