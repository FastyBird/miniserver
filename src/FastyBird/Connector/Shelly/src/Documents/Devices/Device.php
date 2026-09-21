<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Connector\Shelly\Documents\Devices;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\Device::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\Device::TYPE)]
class Device extends DevicesDocuments\Devices\Device
{

	public static function getType(): string
	{
		return Entities\Devices\Device::TYPE;
	}

}
