<?php declare(strict_types = 1);

/**
 * Connector.php
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

namespace FastyBird\Connector\Zigbee2Mqtt\Documents\Connectors;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[CoreDocuments\Mapping\Document(entity: Entities\Connectors\Connector::class)]
#[CoreDocuments\Mapping\DiscriminatorEntry(name: Entities\Connectors\Connector::TYPE)]
class Connector extends DevicesDocuments\Connectors\Connector
{

	public static function getType(): string
	{
		return Entities\Connectors\Connector::TYPE;
	}

}
