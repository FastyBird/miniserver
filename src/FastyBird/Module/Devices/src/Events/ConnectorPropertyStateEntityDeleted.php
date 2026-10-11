<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Events;

use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;
use Symfony\Contracts\EventDispatcher;

/**
 * Connector property state entity was deleted event
 */
class ConnectorPropertyStateEntityDeleted extends EventDispatcher\Event
{

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly Sources\Source $source,
	)
	{
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

}
