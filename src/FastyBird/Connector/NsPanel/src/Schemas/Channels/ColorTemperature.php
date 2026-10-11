<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel color temperature channel entity schema
 *
 * @extends Channel<Entities\Channels\ColorTemperature>
 */
final class ColorTemperature extends Channel
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/channel/' . Entities\Channels\ColorTemperature::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Channels\ColorTemperature::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
