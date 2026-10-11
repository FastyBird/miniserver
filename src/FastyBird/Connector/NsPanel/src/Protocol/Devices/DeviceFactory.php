<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Mapping;

/**
 * NS panel device factory interface
 */
interface DeviceFactory
{

	public function create(
		Documents\Connectors\Connector $connector,
		Documents\Devices\Gateway $gateway,
		Documents\Devices\Device $device,
		Mapping\Categories\Category $categoryMetadata,
	): Device;

	/**
	 * @return class-string<Entities\Devices\Device>
	 */
	public function getEntityClass(): string;

}
