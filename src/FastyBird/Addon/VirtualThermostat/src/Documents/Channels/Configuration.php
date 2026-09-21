<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           10.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Documents\Channels;

use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Connector\Virtual\Documents as VirtualDocuments;
use FastyBird\Core\Documents\Application as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\Configuration::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\Configuration::TYPE)]
class Configuration extends VirtualDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return Entities\Channels\Configuration::TYPE;
	}

}
