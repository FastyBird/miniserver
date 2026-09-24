<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Schemas\Devices;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * FastyBird MQTT device entity schema
 *
 * @extends DevicesSchemas\Devices\Device<Entities\Devices\Device>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device extends DevicesSchemas\Devices\Device
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = Sources\Connector::FB_MQTT->value . '/device/' . Entities\Devices\Device::TYPE;

	public function getEntityClass(): string
	{
		return Entities\Devices\Device::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

}
