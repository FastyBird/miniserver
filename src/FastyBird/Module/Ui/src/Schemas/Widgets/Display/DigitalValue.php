<?php declare(strict_types = 1);

/**
 * DigitalValue.php
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

namespace FastyBird\Module\Ui\Schemas\Widgets\Display;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Ui\Entities;

/**
 * Digital value widget display entity schema
 *
 * @template T of Entities\Widgets\Displays\DigitalValue
 * @extends  Display<T>
 *
 * @package         FastyBird:UIModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DigitalValue extends Display
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::UI->value . '/display/' . Entities\Widgets\Displays\DigitalValue::TYPE;

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Widgets\Displays\DigitalValue::class;
	}

}
