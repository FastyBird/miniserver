<?php declare(strict_types = 1);

namespace FastyBird\Connector\Virtual\Drivers;

use FastyBird\Connector\Virtual\Documents;

/**
 * Driver factory
 */
interface DriverFactory
{

	public const ?string DEVICE_TYPE = null;

	public function create(Documents\Devices\Device $device): Driver;

}
