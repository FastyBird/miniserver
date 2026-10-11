<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Digital sensor widget entity schema
 *
 * @template  T of Entities\Widgets\DigitalSensor
 * @extends   Widget<T>
 */
final class DigitalSensor extends Widget
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/widget/' . Entities\Widgets\DigitalSensor::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Widgets\DigitalSensor::class;
	}

}
