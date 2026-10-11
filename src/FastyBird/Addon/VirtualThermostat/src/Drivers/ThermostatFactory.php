<?php declare(strict_types = 1);

/**
 * ThermostatFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Drivers
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Drivers;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Drivers\DriverFactory;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

/**
 * Thermostat service factory
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Drivers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ThermostatFactory extends DriverFactory
{

	public const string DEVICE_TYPE = Entities\Devices\Device::TYPE;

	public function create(DevicesDocuments\Devices\Device $device): Thermostat;

}
