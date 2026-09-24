<?php declare(strict_types = 1);

/**
 * InputSwitch.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           23.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\InputSwitch::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\InputSwitch::TYPE)]
class InputSwitch extends Shelly
{

	public static function getType(): string
	{
		return Entities\Channels\InputSwitch::TYPE;
	}

}
