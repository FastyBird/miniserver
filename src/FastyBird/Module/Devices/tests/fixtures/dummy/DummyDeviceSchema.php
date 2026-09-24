<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas;

final class DummyDeviceSchema extends Schemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::DEVICES->value . '/device/' . DummyDeviceEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyDeviceEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
