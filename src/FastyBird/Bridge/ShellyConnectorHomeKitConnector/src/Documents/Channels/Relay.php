<?php declare(strict_types = 1);

/**
 * Relay.php
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

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\Relay::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\Relay::TYPE)]
class Relay extends Shelly
{

	public static function getType(): string
	{
		return Entities\Channels\Relay::TYPE;
	}

}
