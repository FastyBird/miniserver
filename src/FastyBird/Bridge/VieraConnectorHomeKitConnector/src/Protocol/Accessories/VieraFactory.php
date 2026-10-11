<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents as VieraConnectorHomeKitConnectorDocuments;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Override;
use function assert;

/**
 * HAP viera accessory factory
 */
final class VieraFactory implements HomeKitProtocol\Accessories\AccessoryFactory
{

	#[Override]
	public function create(
		string $name,
		int|null $aid,
		HomeKitTypes\AccessoryCategory $category,
		HomeKitDocuments\Devices\Device $device,
	): Viera
	{
		assert($device instanceof VieraConnectorHomeKitConnectorDocuments\Devices\Viera);

		return new Viera($name, $aid, $category, $device);
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Devices\Viera::class;
	}

}
