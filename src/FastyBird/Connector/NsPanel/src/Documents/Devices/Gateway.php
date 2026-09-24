<?php declare(strict_types = 1);

/**
 * Gateway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Connector\NsPanel\Documents\Devices;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Devices\Gateway::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Devices\Gateway::TYPE)]
class Gateway extends Device
{

	public static function getType(): string
	{
		return Entities\Devices\Gateway::TYPE;
	}

}
