<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Symfony\Contracts\EventDispatcher;

/**
 * After message published event
 */
class AfterMessagePublished extends EventDispatcher\Event
{

	public function __construct(
		private readonly MetadataTypes\Sources\Source $source,
		private readonly string $routingKey,
		private readonly ApplicationDocuments\Document|null $entity,
	)
	{
	}

	public function getSource(): MetadataTypes\Sources\Source
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
