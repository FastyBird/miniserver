<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * After message handled event
 */
final class AfterMessageHandled extends EventDispatcher\Event
{

	public function __construct(private readonly string $message)
	{
	}

	public function getMessage(): string
	{
		return $this->message;
	}

}
