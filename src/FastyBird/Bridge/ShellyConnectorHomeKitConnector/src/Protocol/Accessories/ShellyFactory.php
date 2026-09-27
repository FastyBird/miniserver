<?php declare(strict_types = 1);

/**
 * ShellyFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents as ShellyConnectorHomeKitConnectorDocuments;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use function assert;

/**
 * HAP shelly accessory factory
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ShellyFactory implements HomeKitProtocol\Accessories\AccessoryFactory
{

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

	public function getEntityClass(): string
	{
		return Entities\Devices\Shelly::class;
	}

}
