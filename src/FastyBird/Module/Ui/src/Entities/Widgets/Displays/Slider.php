<?php declare(strict_types = 1);

/**
 * Slider.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets\Displays;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Module\Ui\Entities;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Slider extends Display implements Entities\Widgets\Displays\Parameters\MinimumValue,
	Entities\Widgets\Displays\Parameters\MaximumValue,
	Entities\Widgets\Displays\Parameters\StepValue,
	Entities\Widgets\Displays\Parameters\Precision
{

	use Entities\Widgets\Displays\Parameters\TMinimumValue;
	use Entities\Widgets\Displays\Parameters\TMaximumValue;
	use Entities\Widgets\Displays\Parameters\TStepValue;
	use Entities\Widgets\Displays\Parameters\TPrecision;

	public const TYPE = 'slider';

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

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * {@inheritDoc}
	 */
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
