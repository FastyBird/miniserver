<?php declare(strict_types = 1);

/**
 * CloudFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Types;

/**
 * Cloud client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface CloudFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::CLOUD;

	public function create(Documents\Connectors\Connector $connector): Cloud;

}
