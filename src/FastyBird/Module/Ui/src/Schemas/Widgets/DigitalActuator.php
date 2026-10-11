<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;

/**
 * Digital actuator widget entity schema
 *
 * @template  T of Entities\Widgets\DigitalActuator
 * @extends   Widget<T>
 */
final class DigitalActuator extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/widget/' . Entities\Widgets\DigitalActuator::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\DigitalActuator::class;
	}

}
