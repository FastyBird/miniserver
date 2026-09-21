<?php declare(strict_types = 1);

/**
 * AnalogSensor.php
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

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui\Entities;

/**
 * Analog sensor widget entity schema
 *
 * @template  T of Entities\Widgets\AnalogSensor
 * @extends   Widget<T>
 *
 * @package         FastyBird:UIModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AnalogSensor extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::UI->value . '/widget/' . Entities\Widgets\AnalogSensor::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\AnalogSensor::class;
	}

}
