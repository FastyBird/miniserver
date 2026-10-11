<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel third-party device entity schema
 *
 * @extends Device<Entities\Devices\ThirdPartyDevice>
 */
final class ThirdPartyDevice extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/device/' . Entities\Devices\ThirdPartyDevice::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\ThirdPartyDevice::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
