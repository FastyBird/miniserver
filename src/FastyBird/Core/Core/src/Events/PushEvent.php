<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Entities\WebSockets\PushMessages;
use FastyBird\Core\Entities\WsServer\Topics as TopicEntities;
use Symfony\Contracts\EventDispatcher;

/**
 * Message pushed into topic event
 */
final class PushEvent extends EventDispatcher\Event
{

	public function __construct(
		private PushMessages\IMessage $message,
		private string $provider,
		private TopicEntities\ITopic $topic,
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

	public function getTopic(): TopicEntities\ITopic
	{
		return $this->topic;
	}

}
