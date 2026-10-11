<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\States;
use Symfony\Contracts\EventDispatcher;

/**
 * Connector property state entity was created event
 */
final class ConnectorPropertyStateEntityCreated extends EventDispatcher\Event
{

	public function __construct(
		private readonly Documents\Connectors\Properties\Dynamic $property,
		private readonly States\ConnectorProperty $read,
		private readonly States\ConnectorProperty $get,
		private readonly Sources\Source $source,
	)
	{
	}

	public function getProperty(): Documents\Connectors\Properties\Dynamic
	{
		return $this->property;
	}

	public function getRead(): States\ConnectorProperty
	{
		return $this->read;
	}

	public function getGet(): States\ConnectorProperty
	{
		return $this->get;
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

}
