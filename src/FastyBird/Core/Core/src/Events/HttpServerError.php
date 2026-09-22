<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * HTTP server connection error event
 */
final class HttpServerError extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable $ex)
	{
	}

	public function getException(): Throwable
	{
		return $this->ex;
	}

}
