<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Events;

use FastyBird\Core\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event triggered when document metadata are loaded
 *
 * @template T of Documents\Document
 */
final class LoadClassMetadata extends EventDispatcher\Event
{

	/**
	 * @param Documents\Mapping\ClassMetadata<T> $classMetadata
	 */
	public function __construct(
		private readonly Documents\Mapping\ClassMetadata $classMetadata,
	)
	{
	}

	/**
	 * @return Documents\Mapping\ClassMetadata<T>
	 */
	public function getClassMetadata(): Documents\Mapping\ClassMetadata
	{
		return $this->classMetadata;
	}

}
