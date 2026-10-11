<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after connector has been started
 */
final class AfterConnectorExecutionStart extends EventDispatcher\Event
{

	public function __construct(private readonly Documents\Connectors\Connector $connector)
	{
	}

	public function getConnector(): Documents\Connectors\Connector
	{
		return $this->connector;
	}

}
