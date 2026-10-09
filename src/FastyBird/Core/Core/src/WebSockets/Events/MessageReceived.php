<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Server\Wrapper for a message on an established connection, before the protocol
 * hands it to the application. Subscribers\Client re-authenticates the client on it; a client a
 * listener closes never reaches the application.
 */
final class MessageReceived extends EventDispatcher\Event
{

	public function __construct(
		private readonly Entities\Client $client,
		private readonly Handshake\Request $httpRequest,
		private readonly string $message,
	)
	{
	}

	public function getClient(): Entities\Client
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
