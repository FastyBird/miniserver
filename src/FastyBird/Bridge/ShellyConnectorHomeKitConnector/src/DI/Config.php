<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\DI;

/**
 * The fbShellyConnectorHomeKitConnectorBridge configuration, which
 * ShellyConnectorHomeKitConnectorExtension::getConfigSchema() declares and casts to this class
 */
final readonly class Config
{

	public function __construct(public readonly bool $apiPrefix)
	{
	}

}
