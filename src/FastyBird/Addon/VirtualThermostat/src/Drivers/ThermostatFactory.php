<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Drivers;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Drivers\DriverFactory;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

/**
 * Thermostat service factory
 */
interface ThermostatFactory extends DriverFactory
{

	public const string DEVICE_TYPE = Entities\Devices\Device::TYPE;

	public function create(DevicesDocuments\Devices\Device $device): Thermostat;

}
