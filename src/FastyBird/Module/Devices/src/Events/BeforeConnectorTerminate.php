<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Connectors;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired before connector has been terminated
 */
class BeforeConnectorTerminate extends EventDispatcher\Event
{

	public function __construct(private readonly Connectors\Connector $connector)
	{
	}

	public function getConnector(): Connectors\Connector
	{
		return $this->connector;
	}

}
