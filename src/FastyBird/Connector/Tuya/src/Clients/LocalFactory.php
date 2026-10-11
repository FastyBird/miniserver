<?php declare(strict_types = 1);

/**
 * LocalFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Types;

/**
 * Local devices client factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface LocalFactory extends ClientFactory
{

	public const Types\ClientMode MODE = Types\ClientMode::LOCAL;

	public function create(Documents\Connectors\Connector $connector): Local;

}
