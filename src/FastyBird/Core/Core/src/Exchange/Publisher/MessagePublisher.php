<?php declare(strict_types = 1);

namespace FastyBird\Core\Exchange\Publisher;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;

/**
 * Exchange publisher interface
 */
interface MessagePublisher
{

	public const string ROUTING_KEY_PREFIX = 'fb.exchange';

	public function publish(
		Sources\Source $source,
		string $routingKey,
		Documents\Document|null $entity,
	): bool;

}
