<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Schemas\Widgets\Display;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Ui\Entities;
use Override;

/**
 * Digital value widget display entity schema
 *
 * @template T of Entities\Widgets\Displays\DigitalValue
 * @extends  Display<T>
 */
final class DigitalValue extends Display
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::UI->value . '/display/' . Entities\Widgets\Displays\DigitalValue::TYPE;

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\Displays\DigitalValue::class;
	}

}
