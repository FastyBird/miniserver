<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Server\Wrapper after a successful upgrade, before the application opens the
 * connection. Subscribers\Client authenticates the client on it.
 */
final class ClientConnected extends EventDispatcher\Event
{

	public function __construct(
		private readonly Entities\ConnectedClient $client,
		private readonly Handshake\Request $httpRequest,
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

}
