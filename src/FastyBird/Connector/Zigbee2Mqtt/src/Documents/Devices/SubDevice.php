<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Documents\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Core\Documents as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Devices\SubDevice::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Devices\SubDevice::TYPE)]
class SubDevice extends Device
{

	public static function getType(): string
	{
		return Entities\Devices\SubDevice::TYPE;
	}

}
