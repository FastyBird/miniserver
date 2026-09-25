<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities\PushMessages;
use FastyBird\Core\WebSockets\Entities\Topics;
use Symfony\Contracts\EventDispatcher;

/**
 * Message pushed into topic event
 */
final class PushEvent extends EventDispatcher\Event
{

	public function __construct(
		private PushMessages\IMessage $message,
		private string $provider,
		private Topics\ITopic $topic,
	)
	{
	}

	public function getMessage(): PushMessages\IMessage
	{
		return $this->message;
	}

	public function getProvider(): string
	{
		return $this->provider;
	}

	public function getTopic(): Topics\ITopic
	{
		return $this->topic;
	}

}
