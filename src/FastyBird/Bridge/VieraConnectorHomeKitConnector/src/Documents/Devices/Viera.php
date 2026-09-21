<?php declare(strict_types = 1);

/**
 * Viera.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents\Devices;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Core\Documents as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\Viera::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Viera::TYPE)]
class Viera extends HomeKitDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Viera::TYPE;
	}

}
