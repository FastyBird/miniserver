<?php declare(strict_types = 1);

/**
 * TPrecision.php
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
use function intval;
use function is_numeric;
use function is_string;

/**
 * Display precision parameter
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
