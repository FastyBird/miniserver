<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Consumers;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Values\Types\Sources;

/**
 * Exchange consumer interface
 */
interface Consumer
{

	public function consume(
		Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void;

}
