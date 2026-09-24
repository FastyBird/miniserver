<?php declare(strict_types = 1);

namespace FastyBird\Core\Documents\Events;

use FastyBird\Core\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event triggered after document is created
 *
 * @template T of Documents\Document
 */
final class PostLoad extends EventDispatcher\Event
{

	/**
	 * @param T $document
	 */
	public function __construct(private readonly Documents\Document $document)
	{
	}

	/**
	 * @return T
	 */
	public function getDocument(): Documents\Document
	{
		return $this->document;
	}

}
