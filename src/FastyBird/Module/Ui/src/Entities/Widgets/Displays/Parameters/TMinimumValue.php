<?php declare(strict_types = 1);

/**
 * TMinimumValue.php
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

use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use function floatval;
use function is_numeric;
use function is_string;

/**
 * Display minimum value parameter
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait TMinimumValue
{

	#[IPubDoctrine\Crud(writable: true)]
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
