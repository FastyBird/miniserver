<?php declare(strict_types = 1);

/**
 * DigitalActuatorSchema.php
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
 * Digital actuator widget entity schema
 *
 * @template  T of Entities\Widgets\DigitalActuator
 * @extends   Widget<T>
 *
 * @package         FastyBird:UIModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DigitalActuator extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::UI->value . '/widget/' . Entities\Widgets\DigitalActuator::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\DigitalActuator::class;
	}

}
