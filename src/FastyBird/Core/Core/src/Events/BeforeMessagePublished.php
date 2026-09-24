<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;
use Symfony\Contracts\EventDispatcher;

/**
 * Before message published event
 */
final class BeforeMessagePublished extends EventDispatcher\Event
{

	public function __construct(
		private readonly Sources\Source $source,
		private readonly string $routingKey,
		private readonly Documents\Document|null $entity,
	)
	{
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

	public function getRoutingKey(): string
	{
		return $this->routingKey;
	}

	public function getEntity(): Documents\Document|null
	{
		return $this->entity;
	}

}
