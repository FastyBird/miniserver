<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Publisher;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Types\Metadata as MetadataTypes;

/**
 * Exchange publisher interface
 */
interface Publisher
{

	public function publish(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): bool;

}
