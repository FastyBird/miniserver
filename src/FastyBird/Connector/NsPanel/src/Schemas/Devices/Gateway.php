<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Schemas\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Values\Types\Sources;

/**
 * NS Panel gateway entity schema
 *
 * @template T of Entities\Devices\Gateway
 * @extends  Device<T>
 */
final class Gateway extends Device
{

	/**
	 * Define entity schema type string
	 */
	public const string SCHEMA_TYPE = Sources\Connector::NS_PANEL->value . '/device/' . Entities\Devices\Gateway::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Gateway::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
