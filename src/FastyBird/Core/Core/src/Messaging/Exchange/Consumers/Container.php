<?php declare(strict_types = 1);

namespace FastyBird\Core\Messaging\Exchange\Consumers;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Events;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Override;
use Psr\EventDispatcher as PsrEventDispatcher;
use SplObjectStorage;

/**
 * Exchange consumer proxy
 */
final class Container implements Consumer
{

	/** @var SplObjectStorage<Consumer, Info> */
	private SplObjectStorage $consumers;

	public function __construct(
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->consumers = new SplObjectStorage();
	}

	#[Override]
	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void
	{
		$this->dispatcher?->dispatch(new Events\BeforeMessageConsumed($source, $routingKey, $document));

		$this->consumers->rewind();

		while ($this->consumers->valid()) {
			$consumer = $this->consumers->current();

			$info = $this->consumers->getInfo();

			if (
				$info->isEnabled()
				&& (
					$info->getRoutingKey() === null
					|| $info->getRoutingKey() === $routingKey
				)
			) {
				$consumer->consume($source, $routingKey, $document);
			}

			$this->consumers->next();
		}

		$this->dispatcher?->dispatch(new Events\AfterMessageConsumed($source, $routingKey, $document));
	}

	public function register(Consumer $consumer, string|null $routingKey, bool $status = true): void
	{
		if (!$this->consumers->offsetExists($consumer)) {
			$this->consumers->offsetSet(
				$consumer,
				new Info($routingKey, $status),
			);
		}
	}

	/**
	 * @param class-string<Consumer> $name
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function enable(string $name): void
	{
		$this->consumers->rewind();

		while ($this->consumers->valid()) {
			$consumer = $this->consumers->current();

			$info = $this->consumers->getInfo();

			if ($consumer::class === $name) {
				if (!$info->isEnabled()) {
					$this->consumers->offsetUnset($consumer);
					$this->consumers->offsetSet($consumer, new Info($info->getRoutingKey(), true));
				}

				return;
			}

			$this->consumers->next();
		}

		throw new Exceptions\InvalidArgument('Provided consumer is not registered in container and can not be enabled');
	}

	/**
	 * @param class-string<Consumer> $name
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function disable(string $name): void
	{
		$this->consumers->rewind();

		while ($this->consumers->valid()) {
			$consumer = $this->consumers->current();

			$info = $this->consumers->getInfo();

			if ($consumer::class === $name) {
				if ($info->isEnabled()) {
					$this->consumers->offsetUnset($consumer);
					$this->consumers->offsetSet($consumer, new Info($info->getRoutingKey(), false));
				}

				return;
			}

			$this->consumers->next();
		}

		throw new Exceptions\InvalidArgument(
			'Provided consumer is not registered in container and can not be disabled',
		);
	}

	public function reset(): void
	{
		$this->consumers = new SplObjectStorage();
	}

}
