<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\DI;

/**
 * The fbVirtualThermostatAddonHomeKitConnectorBridge configuration, which
 * VirtualThermostatAddonHomeKitConnectorExtension::getConfigSchema() declares and casts to this
 * class
 */
final readonly class Config
{

	public function __construct(public readonly bool $apiPrefix)
	{
	}

}
