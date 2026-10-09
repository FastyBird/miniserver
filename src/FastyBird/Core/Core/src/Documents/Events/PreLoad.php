<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Events;

use FastyBird\Core\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event triggered before document is created
 *
 * @template T of Documents\Document
 */
final class PreLoad extends EventDispatcher\Event
{

	/**
	 * @param array<mixed> $data
	 * @param class-string<T> $class
	 */
	public function __construct(
		public array $data,
		private readonly string $class,
	)
	{
	}

	/**
	 * @return class-string<T>
	 */
	public function getClass(): string
	{
		return $this->class;
	}

}
