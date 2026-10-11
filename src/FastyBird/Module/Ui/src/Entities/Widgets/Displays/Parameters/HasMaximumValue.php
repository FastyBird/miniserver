<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Core\Persistence\Mapping\Attribute;
use function floatval;
use function is_numeric;
use function is_string;

/**
 * Display maximum value parameter
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait HasMaximumValue
{

	#[Attribute\Crud(writable: true)]
	protected float|null $maximumValue = null;

	public function getMaximumValue(): float|null
	{
		$value = $this->getParam('maximumValue');

		return (is_string($value) || is_numeric($value)) && $value !== '' ? floatval($value) : null;
	}

	public function setMaximumValue(float|null $maximumValue): void
	{
		$this->maximumValue = $maximumValue;

		$this->setParam('maximumValue', $maximumValue);
	}

}
