<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Events;

use Bunny;
use Symfony\Contracts\EventDispatcher;

/**
 * Before message handled event
 */
final class BeforeMessageHandled extends EventDispatcher\Event
{

	public function __construct(private readonly Bunny\Message $message)
	{
	}

	public function getMessage(): Bunny\Message
	{
		return $this->message;
	}

}
