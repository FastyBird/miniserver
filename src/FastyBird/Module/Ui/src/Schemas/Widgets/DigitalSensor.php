<?php declare(strict_types = 1);

/**
 * DigitalSensorSchema.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Schemas\Widgets;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;

/**
 * Digital sensor widget entity schema
 *
 * @template  T of Entities\Widgets\DigitalSensor
 * @extends   Widget<T>
 *
 * @package         FastyBird:UIModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DigitalSensor extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::UI->value . '/widget/' . Entities\Widgets\DigitalSensor::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\DigitalSensor::class;
	}

}
