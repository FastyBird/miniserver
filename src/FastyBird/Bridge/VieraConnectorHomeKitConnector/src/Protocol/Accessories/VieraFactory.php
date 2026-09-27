<?php declare(strict_types = 1);

/**
 * VieraFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           26.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Accessories;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents as VieraConnectorHomeKitConnectorDocuments;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use function assert;

/**
 * HAP viera accessory factory
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VieraFactory implements HomeKitProtocol\Accessories\AccessoryFactory
{

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

	public function getEntityClass(): string
	{
		return Entities\Devices\Viera::class;
	}

}
