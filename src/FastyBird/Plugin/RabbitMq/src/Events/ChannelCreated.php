<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Events;

use Bunny;
use Symfony\Contracts\EventDispatcher;

/**
 * Client channel created event
 */
final class ChannelCreated extends EventDispatcher\Event
{

	public function __construct(private readonly Bunny\Channel $channel)
	{
	}

	public function getChannel(): Bunny\Channel
	{
		return $this->channel;
	}

}
