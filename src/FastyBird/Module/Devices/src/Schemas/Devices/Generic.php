<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Schemas\Devices;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities;
use Override;

/**
 * Generic device entity schema
 *
 * @extends Device<Entities\Devices\Generic>
 */
final class Generic extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Module::DEVICES->value . '/device/' . Entities\Devices\Generic::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\Generic::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
