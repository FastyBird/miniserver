<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;

/**
 * WS client sent message event
 */
final readonly class IncomingMessage
{

	public function __construct(
		private Entities\ConnectedClient $client,
		private Handshake\Request $httpRequest,
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
