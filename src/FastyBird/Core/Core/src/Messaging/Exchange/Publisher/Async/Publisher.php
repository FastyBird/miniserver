<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Publisher\Async;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Values\Types\Sources;
use React\Promise;

/**
 * Exchange asynchronous publisher interface
 */
interface Publisher
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function publish(
		Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): Promise\PromiseInterface;

}
