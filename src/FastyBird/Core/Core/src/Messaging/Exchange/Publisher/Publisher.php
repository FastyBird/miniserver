<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Publisher;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;

/**
 * Exchange publisher interface
 */
interface Publisher
{

	public function publish(
		Sources\Source $source,
		string $routingKey,
		Documents\Document|null $entity,
	): bool;

}
