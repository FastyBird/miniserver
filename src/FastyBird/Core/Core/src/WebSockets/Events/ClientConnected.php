<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;

/**
 * WS client connected to server event
 */
final readonly class ClientConnected
{

	public function __construct(
		private Entities\ConnectedClient $client,
		private Handshake\IRequest $httpRequest,
	)
	{
	}

	public function getClient(): Entities\ConnectedClient
	{
		return $this->client;
	}

	public function getHttpRequest(): Handshake\IRequest
	{
		return $this->httpRequest;
	}

}
