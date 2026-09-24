<?php declare(strict_types = 1);

/**
 * TStepValue.php
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

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Core\Persistence\Mapping\Attribute;
use function floatval;
use function is_numeric;
use function is_string;

/**
 * Display step value parameter
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait TStepValue
{

	#[Attribute\Crud(writable: true)]
	protected float|null $stepValue = null;

	public function getStepValue(): float
	{
		$value = $this->getParam('stepValue', 0.1);

		return (is_string($value) || is_numeric($value)) && $value !== '' ? floatval($value) : 0.1;
	}

	public function setStepValue(float|null $stepValue): void
	{
		$this->stepValue = $stepValue;

		$this->setParam('stepValue', $stepValue);
	}

}
