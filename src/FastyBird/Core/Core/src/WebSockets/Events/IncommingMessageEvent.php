<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Incomming message event
 */
final class IncommingMessageEvent extends EventDispatcher\Event
{

	public function __construct(
		private Entities\ConnectedClient $client,
		private Handshake\Request $httpRequest,
		private string $message,
	)
	{
	}

	public function getClient(): Entities\ConnectedClient
	{
		return $this->client;
	}

	public function getHttpRequest(): Handshake\Request
	{
		return $this->httpRequest;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

}
