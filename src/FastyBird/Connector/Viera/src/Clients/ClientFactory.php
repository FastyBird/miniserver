<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Clients;

use FastyBird\Connector\Viera\Documents;

/**
 * Base device client factory
 */
interface ClientFactory
{

	public function create(Documents\Connectors\Connector $connector): Television;

}
