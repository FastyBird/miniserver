<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Analog sensor widget entity schema
 *
 * @template  T of Entities\Widgets\AnalogSensor
 * @extends   Widget<T>
 */
final class AnalogSensor extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/widget/' . Entities\Widgets\AnalogSensor::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\AnalogSensor::class;
	}

}
