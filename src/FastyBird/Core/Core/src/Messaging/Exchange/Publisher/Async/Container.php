<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Publisher\Async;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Events;
use FastyBird\Core\Values\Types\Sources;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Promise;
use SplObjectStorage;
use Throwable;

/**
 * Exchange async publishers proxy
 */
final class Container implements Publisher
{

	/** @var SplObjectStorage<Publisher, null> */
	private SplObjectStorage $publishers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->publishers = new SplObjectStorage();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	#[Override]
	public function publish(
		Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$promises = [];

		$this->dispatcher?->dispatch(new Events\BeforeMessagePublished($source, $routingKey, $entity));

		$this->publishers->rewind();

		foreach ($this->publishers as $publisher) {
			$promises[] = $publisher->publish($source, $routingKey, $entity);
		}

		Promise\all($promises)
			->then(function () use ($source, $routingKey, $entity, $deferred): void {
				$this->dispatcher?->dispatch(new Events\AfterMessagePublished($source, $routingKey, $entity));

				$deferred->resolve(true);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
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
