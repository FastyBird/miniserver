<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Documents as ApplicationDocuments;
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
		private readonly ApplicationDocuments\Document|null $entity,
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

	public function getEntity(): ApplicationDocuments\Document|null
	{
		return $this->entity;
	}

}
