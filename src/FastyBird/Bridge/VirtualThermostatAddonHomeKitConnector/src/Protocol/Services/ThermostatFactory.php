<?php declare(strict_types = 1);

/**
 * ThermostatFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           29.01.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Services;

use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Documents as VirtualThermostatAddonHomeKitConnectorDocuments;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Ramsey\Uuid;
use function assert;

/**
 * HAP thermostat service factory
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ThermostatFactory implements HomeKitProtocol\Services\ServiceFactory
{

	/**
	 * @param array<string> $requiredCharacteristics
	 * @param array<string> $optionalCharacteristics
	 * @param array<string> $virtualCharacteristics
	 */
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

	public function getEntityClass(): string
	{
		return Entities\Channels\Thermostat::class;
	}

}
