<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\DI;

/**
 * The fbVieraConnectorHomeKitConnectorBridge configuration, which
 * VieraConnectorHomeKitConnectorExtension::getConfigSchema() declares and casts to this class
 */
final readonly class Config
{

	public function __construct(public readonly bool $apiPrefix)
	{
	}

}
