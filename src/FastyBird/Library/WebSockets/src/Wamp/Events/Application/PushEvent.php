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

namespace FastyBird\Library\WebSockets\Wamp\Events\Application;

use FastyBird\Library\WebSockets\Wamp\Entities;
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
		private Entities\PushMessages\IMessage $message,
		private string $provider,
		private Entities\Topics\ITopic $topic,
	)
	{
	}

	public function getMessage(): Entities\PushMessages\IMessage
	{
		return $this->message;
	}

	public function getProvider(): string
	{
		return $this->provider;
	}

	public function getTopic(): Entities\Topics\ITopic
	{
		return $this->topic;
	}

}
