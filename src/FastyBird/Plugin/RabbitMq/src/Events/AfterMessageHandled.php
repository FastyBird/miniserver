<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Events;

use Bunny;
use Symfony\Contracts\EventDispatcher;

/**
 * After message handled event
 */
class AfterMessageHandled extends EventDispatcher\Event
{

	public function __construct(private readonly Bunny\Message $message)
	{
	}

	public function getMessage(): Bunny\Message
	{
		return $this->message;
	}

}
