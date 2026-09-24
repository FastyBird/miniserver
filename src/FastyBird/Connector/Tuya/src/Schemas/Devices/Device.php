<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           25.04.22
 */

namespace FastyBird\Connector\Tuya\Schemas\Devices;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Tuya device entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\Devices\Device>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Connector::TUYA->value . '/device/' . Entities\Devices\Device::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Device::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
