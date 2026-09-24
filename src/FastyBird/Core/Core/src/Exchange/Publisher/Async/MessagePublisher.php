<?php declare(strict_types = 1);

namespace FastyBird\Core\Exchange\Publisher\Async;

use FastyBird\Core\Documents;
use FastyBird\Core\Values\Types\Sources;
use React\Promise;

/**
 * Exchange asynchronous publisher interface
 */
interface MessagePublisher
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function publish(
		Sources\Source $source,
		string $routingKey,
		Documents\Document|null $entity,
	): Promise\PromiseInterface;

}
