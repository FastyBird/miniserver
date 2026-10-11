<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel illumination level channel entity schema
 *
 * @extends Channel<Entities\Channels\IlluminationLevel>
 */
final class IlluminationLevel extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\IlluminationLevel::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\IlluminationLevel::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
