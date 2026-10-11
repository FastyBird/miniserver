<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents as ShellyConnectorHomeKitConnectorDocuments;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Override;
use function assert;

/**
 * HAP shelly accessory factory
 */
final class ShellyFactory implements HomeKitProtocol\Accessories\AccessoryFactory
{

	#[Override]
	public function create(
		string $name,
		int|null $aid,
		HomeKitTypes\AccessoryCategory $category,
		HomeKitDocuments\Devices\Device $device,
	): Shelly
	{
		assert($device instanceof ShellyConnectorHomeKitConnectorDocuments\Devices\Shelly);

		return new Shelly($name, $aid, $category, $device);
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\Shelly::class;
	}

}
