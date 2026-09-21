<?php declare(strict_types = 1);

/**
 * Container.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Publishers
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Core\Messaging\Exchange\Publisher;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Events;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Psr\EventDispatcher as PsrEventDispatcher;
use SplObjectStorage;

/**
 * Exchange publishers proxy
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Container implements Publisher
{

	/** @var SplObjectStorage<Publisher, null> */
	private SplObjectStorage $publishers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->publishers = new SplObjectStorage();
	}

	public function publish(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): bool
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessagePublished($source, $routingKey, $entity));

		$this->publishers->rewind();

		foreach ($this->publishers as $publisher) {
			$publisher->publish($source, $routingKey, $entity);
		}

		$this->dispatcher?->dispatch(new Events\AfterMessagePublished($source, $routingKey, $entity));

		return true;
	}

	public function register(Publisher $publisher): void
	{
		if (!$this->publishers->offsetExists($publisher)) {
			$this->publishers->offsetSet($publisher);
		}
	}

	public function reset(): void
	{
		$this->publishers = new SplObjectStorage();
	}

}
