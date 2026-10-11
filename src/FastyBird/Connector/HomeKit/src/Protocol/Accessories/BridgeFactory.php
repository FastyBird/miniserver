<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Accessories;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;

/**
 * HAP bridge accessory factory
 */
class BridgeFactory
{

	public function create(string $name, Documents\Connectors\Connector $connector): Bridge
	{
		return new Bridge($name, $connector);
	}

	public function getEntityClass(): string
	{
		return Entities\Connectors\Connector::class;
	}

}
