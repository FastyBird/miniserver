<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;
use Override;

/**
 * NS Panel sub-device entity schema
 *
 * @extends Device<Entities\Devices\SubDevice>
 */
final class SubDevice extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/device/' . Entities\Devices\SubDevice::TYPE;

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\SubDevice::class;
	}

	#[Override]
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
