<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Core\Persistence\Mapping\Attribute;
use function floatval;
use function is_numeric;
use function is_string;

/**
 * Display minimum value parameter
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait TMinimumValue
{

	#[Attribute\Crud(writable: true)]
	protected float|null $minimumValue = null;

	public function getMinimumValue(): float|null
	{
		$value = $this->getParam('minimumValue');

		return (is_string($value) || is_numeric($value)) && $value !== '' ? floatval($value) : null;
	}

	public function setMinimumValue(float|null $minimumValue): void
	{
		$this->minimumValue = $minimumValue;

		$this->setParam('minimumValue', $minimumValue);
	}

}
