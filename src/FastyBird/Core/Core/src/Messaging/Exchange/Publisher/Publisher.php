<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Publisher;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Values\Types\Sources;

/**
 * Exchange publisher interface
 */
interface Publisher
{

	public function publish(
		Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): bool;

}
