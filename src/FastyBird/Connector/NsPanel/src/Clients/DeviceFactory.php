<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Types;

/**
 * Connector third-party device client factory
 */
interface DeviceFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::DEVICE;

	public function create(Documents\Connectors\Connector $connector): Device;

}
