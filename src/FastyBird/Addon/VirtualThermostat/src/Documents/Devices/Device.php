<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Documents\Devices;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Core\Documents as CoreDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Devices\Device::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Device::TYPE)]
class Device extends VirtualDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Device::TYPE;
	}

}
