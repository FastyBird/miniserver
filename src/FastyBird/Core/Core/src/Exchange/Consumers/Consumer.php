<?php declare(strict_types = 1);

namespace FastyBird\Core\Exchange\Consumers;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;

/**
 * Exchange consumer interface
 */
interface Consumer
{

	public function consume(
		Sources\Source $source,
		string $routingKey,
		Documents\Document|null $document,
	): void;

}
