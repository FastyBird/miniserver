<?php declare(strict_types = 1);

/**
 * ClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Clients;

use FastyBird\Connector\FbMqtt\Documents;

/**
 * Base client factory
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ClientFactory
{

	public const string VERSION_CONSTANT_NAME = 'VERSION';

	public function create(Documents\Connectors\Connector $connector): Client;

}
