<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyChannelSchema extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Module::DEVICES->value . '/channel/' . DummyChannelEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyChannelEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
