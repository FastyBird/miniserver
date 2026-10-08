<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Dispatched by Commands\WsServer when the server's socket reports an error, before the command
 * stops the loop.
 */
final class ServerFailed extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable $ex)
	{
	}

	public function getException(): Throwable
	{
		return $this->ex;
	}

}
