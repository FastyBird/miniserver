<?php declare(strict_types = 1);

/**
 * TMaximumValue.php
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
 * Display maximum value parameter
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @method void setParam(string $key, $value = null)
 * @method mixed getParam(string $key, $default = null)
 */
trait TMaximumValue
{

	#[IPubDoctrine\Crud(writable: true)]
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
