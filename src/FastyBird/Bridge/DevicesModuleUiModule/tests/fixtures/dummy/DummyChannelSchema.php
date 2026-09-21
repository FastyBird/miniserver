<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

final class DummyChannelSchema extends DevicesSchemas\Channels\Channel
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::DEVICES->value . '/channel/' . DummyChannelEntity::TYPE;

	public function getEntityClass(): string
	{
		return DummyChannelEntity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
