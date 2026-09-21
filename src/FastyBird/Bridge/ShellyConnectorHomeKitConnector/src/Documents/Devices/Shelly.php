<?php declare(strict_types = 1);

/**
 * Shelly.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents\Application as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\Shelly::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Shelly::TYPE)]
class Shelly extends HomeKitDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Shelly::TYPE;
	}

}
