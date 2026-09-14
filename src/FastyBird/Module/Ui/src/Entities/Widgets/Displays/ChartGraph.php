<?php declare(strict_types = 1);

/**
 * ChartGraph.php
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
use FastyBird\Core\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use FastyBird\Module\Ui\Entities;
use function array_merge;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class ChartGraph extends Display implements Entities\Widgets\Displays\Parameters\MinimumValue,
	Entities\Widgets\Displays\Parameters\MaximumValue,
	Entities\Widgets\Displays\Parameters\StepValue,
	Entities\Widgets\Displays\Parameters\Precision
{

	use Entities\Widgets\Displays\Parameters\TMinimumValue;
	use Entities\Widgets\Displays\Parameters\TMaximumValue;
	use Entities\Widgets\Displays\Parameters\TStepValue;
	use Entities\Widgets\Displays\Parameters\TPrecision;

	public const TYPE = 'chart-graph';

	#[IPubDoctrine\Crud(writable: true)]
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
