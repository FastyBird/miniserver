<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after connector has been terminated
 */
class AfterConnectorExecutionTerminate extends EventDispatcher\Event
{

	public function __construct(
		private readonly Connectors\Connector $service,
		private readonly Documents\Connectors\Connector $connector,
	)
	{
	}

	public function getService(): Connectors\Connector
	{
		return $this->service;
	}

	public function getConnector(): Documents\Connectors\Connector
	{
		return $this->connector;
	}

}
