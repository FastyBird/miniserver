<?php declare(strict_types = 1);

/**
 * GatewayFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           13.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Types;

/**
 * Connector gateway client factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface GatewayFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::GATEWAY;

	public function create(Documents\Connectors\Connector $connector): Gateway;

}
