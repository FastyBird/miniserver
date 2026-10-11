<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities\Widgets\Displays\Parameters;

use FastyBird\Core\Persistence\Mapping\Attribute;
use function intval;
use function is_numeric;
use function is_string;

/**
 * Display precision parameter
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait TPrecision
{

	#[Attribute\Crud(writable: true)]
	protected int|null $precision = null;

	public function getPrecision(): int
	{
		$value = $this->getParam('precision', 2);

		return (is_string($value) || is_numeric($value)) && $value !== '' ? intval($value) : 2;
	}

	public function setPrecision(int|null $precision): void
	{
		$this->precision = $precision;

		$this->setParam('precision', $precision);
	}

}
