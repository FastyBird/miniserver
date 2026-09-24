<?php declare(strict_types = 1);

namespace FastyBird\Core\Exchange\Events;

use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Exchange service occurred and error
 */
final class ExchangeError extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable|null $ex = null)
	{
	}

	public function getException(): Throwable|null
	{
		return $this->ex;
	}

}
