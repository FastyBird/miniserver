<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Analog actuator widget entity schema
 *
 * @template  T of Entities\Widgets\AnalogActuator
 * @extends   Widget<T>
 */
final class AnalogActuator extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/widget/' . Entities\Widgets\AnalogActuator::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\AnalogActuator::class;
	}

}
