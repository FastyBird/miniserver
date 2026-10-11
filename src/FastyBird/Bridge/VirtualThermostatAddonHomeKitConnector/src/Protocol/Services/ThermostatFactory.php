<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Services;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents as VirtualThermostatAddonHomeKitConnectorDocuments;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Override;
use Ramsey\Uuid;
use function assert;

/**
 * HAP thermostat service factory
 */
class ThermostatFactory implements HomeKitProtocol\Services\ServiceFactory
{

	/**
	 * @param array<string> $requiredCharacteristics
	 * @param array<string> $optionalCharacteristics
	 * @param array<string> $virtualCharacteristics
	 */
	#[Override]
	public function create(
		Uuid\UuidInterface $typeId,
		HomeKitTypes\ServiceType $type,
		HomeKitProtocol\Accessories\Accessory $accessory,
		HomeKitDocuments\Channels\Channel|null $channel = null,
		array $requiredCharacteristics = [],
		array $optionalCharacteristics = [],
		array $virtualCharacteristics = [],
		bool $primary = false,
		bool $hidden = false,
	): Thermostat
	{
		assert($channel instanceof VirtualThermostatAddonHomeKitConnectorDocuments\Channels\Thermostat);

		return new Thermostat(
			$typeId,
			$type,
			$accessory,
			$channel,
			$requiredCharacteristics,
			$optionalCharacteristics,
			$virtualCharacteristics,
			$primary,
			$hidden,
		);
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\Thermostat::class;
	}

}
