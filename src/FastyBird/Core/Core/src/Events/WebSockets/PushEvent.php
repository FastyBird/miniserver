<?php declare(strict_types = 1);

/**
 * PushEvent.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.11.19
 */

namespace FastyBird\Core\Events\WebSockets;

use FastyBird\Core\Entities\WebSockets\PushMessages;
use FastyBird\Core\Entities\WsServer\Topics as TopicEntities;
use Symfony\Contracts\EventDispatcher;

/**
 * Message pushed into topic event
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
